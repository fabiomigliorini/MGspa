<?php

namespace AgroBateria;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * O pátio, do lado de fora: monta a carga como o app monta, calcula e rateia
 * como o app calcula (stores/carga.js::salvar -> desconto.js + ratearPontos) e
 * manda pelo MESMO endpoint (POST v1/carga/sincronizar), etapa por etapa.
 *
 * `etapa` é o passo pendente: cada bloco salvo avança uma etapa, e o último
 * clique (depois da conferência) grava FINALIZADO.
 */
final class Patio
{
    public const ETAPAS = [
        'ENTRADA' => ['PBT', 'CLASSIFICACAO', 'TARA', 'FINALIZADO'],
        'SAIDA' => ['TARA', 'PBT', 'FISCAL', 'FINALIZADO'],
        'TRANSFERENCIA' => ['PBT', 'TARA', 'FINALIZADO'],
    ];
    public const CONTA = ['PLANTIO' => 'codplantio', 'UNIDADE' => 'codunidadearmazenadora', 'CONTRATO' => 'codcontrato'];

    private static int $placa = 0;

    public function __construct(public Api $api, public Massa $m)
    {
    }

    /**
     * Carga nova, como o pátio abre: $origens/$destinos = [[contatipo, cod, %?], ...].
     * Sem % informado, divide 100 igual (distribuirPercentual: resto no último).
     */
    public function nova(string $sentido, string $cultura, array $origens, array $destinos, array $extra = []): array
    {
        return array_replace([
            'uuid' => (string) Str::uuid(),
            'codsafra' => $this->m->safra[$cultura],
            'sentido' => $sentido,
            'etapa' => static::ETAPAS[$sentido][0],
            'data' => $this->m->data(),
            'inativo' => null,
            'placa' => static::placa(),
            'placacarreta' => null,
            'placacarreta2' => null,
            'motorista' => Massa::PREFIXO . ' MOTORISTA',
            'codveiculo' => null,
            'codpessoamotorista' => null,
            'observacao' => Massa::PREFIXO,
            'pbt' => null,
            'tara' => null,
            'classificacao' => [],
            'pontos' => array_merge(static::pontos('ORIGEM', $origens), static::pontos('DESTINO', $destinos)),
            '_cultura' => $cultura,
        ], $extra);
    }

    private static function pontos(string $papel, array $lista): array
    {
        $n = count($lista);
        $base = floor((100 / $n) * 10) / 10;
        $acc = 0.0;
        $out = [];
        foreach (array_values($lista) as $i => $item) {
            [$tipo, $cod] = $item;
            $perc = $item[2] ?? null;
            if ($perc === null) {
                $perc = $i === $n - 1 ? round((100 - $acc) * 10) / 10 : $base;
                $acc += $base;
            }
            $ponto = ['papel' => $papel, 'contatipo' => $tipo, 'codplantio' => null, 'codunidadearmazenadora' => null, 'codcontrato' => null];
            $ponto[static::CONTA[$tipo]] = $cod;
            $out[] = $ponto + ['percentual' => $perc, 'liquido' => null, 'numeronf' => null, 'valornf' => null, 'chavenf' => null];
        }
        return $out;
    }

    public static function placa(): string
    {
        $n = ++static::$placa;
        return 'ZZ' . chr(65 + intdiv($n, 1000) % 26) . ($n % 10) . chr(65 + $n % 26) . str_pad((string) ($n % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Payload do jeito do app: calcula peso e desconto como o desconto.js (em
     * gramas) com os parâmetros que o APARELHO tem, rateia o líquido em kg
     * inteiros pelo % e tira o `percentual` antes do POST.
     */
    public function payload(array $c, ?array $parametros = null): array
    {
        $parametros ??= $this->m->parametros($c['_cultura']);
        $calc = Referencia::descontoPatioAtual($c, $parametros);
        $liq = $calc['liquido'];
        foreach (['ORIGEM', 'DESTINO'] as $papel) {
            $idx = array_keys(array_filter($c['pontos'], fn ($p) => $p['papel'] === $papel));
            if (!$idx) {
                continue;
            }
            $ultimo = end($idx);
            $acc = 0.0;
            foreach ($idx as $i) {
                if (!($liq > 0)) {
                    $c['pontos'][$i]['liquido'] = null;
                } elseif ($i === $ultimo) {
                    $c['pontos'][$i]['liquido'] = Referencia::jsRound($liq - $acc);
                } else {
                    $kg = Referencia::jsRound($liq * ((float) $c['pontos'][$i]['percentual']) / 100);
                    $c['pontos'][$i]['liquido'] = $kg;
                    $acc += $kg;
                }
            }
        }
        $c['bruto'] = $calc['bruto'];
        $c['desconto'] = $calc['desconto'];
        $c['liquido'] = $liq;
        foreach ($c['pontos'] as &$p) {
            unset($p['percentual']);
        }
        unset($p, $c['_cultura']);
        return $c;
    }

    /**
     * `&$c` (TASK-180): a resposta traz a `versao` que o servidor gravou, e o
     * aparelho de verdade guarda ela pra mandar no proximo envio — sem isso um
     * `$velha = $c` tirado depois desta chamada nunca carregaria uma versao
     * realmente velha, e R3/R4 nunca exercitariam o 409 de verdade.
     */
    public function enviar(array &$c, ?array $parametros = null, string $rotulo = 'POST v1/carga/sincronizar'): Resposta
    {
        $resp = $this->api->post('v1/carga/sincronizar', $this->payload($c, $parametros), $rotulo);
        if ($resp->ok()) {
            $dado = $resp->dado();
            if (is_array($dado) && array_key_exists('versao', $dado)) {
                $c['versao'] = $dado['versao'];
            }
        }
        return $resp;
    }

    /**
     * Os estados sucessivos da carga, um por gravação do pátio, até FINALIZADO.
     * $opc: leituras [codparam => %], nf (numero/valor nos destinos contrato).
     *
     * @return array[] cargas completas, na ordem de envio
     */
    public static function passos(array $c, float $pbt, float $tara, array $opc = []): array
    {
        $leituras = [];
        foreach ($opc['leituras'] ?? [] as $cod => $v) {
            $leituras[] = ['codparametroclassificacao' => $cod, 'leitura' => $v];
        }
        $etapas = static::ETAPAS[$c['sentido']];
        $estados = [$c];
        $prox = fn ($e) => $etapas[array_search($e, $etapas, true) + 1];

        foreach ($etapas as $etapa) {
            switch ($etapa) {
                case 'PBT':
                    $c['pbt'] = $pbt;
                    if ($c['sentido'] !== 'ENTRADA' && $leituras) {
                        $c['classificacao'] = $leituras; // expedição/transferência com leitura (T2)
                    }
                    break;
                case 'TARA':
                    $c['tara'] = $tara;
                    break;
                case 'CLASSIFICACAO':
                    $c['classificacao'] = $leituras;
                    break;
                case 'FISCAL':
                    foreach ($c['pontos'] as &$p) {
                        if ($p['papel'] === 'DESTINO' && $p['contatipo'] === 'CONTRATO') {
                            $p['numeronf'] = (string) ($opc['numeronf'] ?? random_int(1000, 999999));
                            $p['valornf'] = $opc['valornf'] ?? 1000.00;
                        }
                    }
                    unset($p);
                    break;
                case 'FINALIZADO':
                    $c['etapa'] = 'FINALIZADO';
                    $estados[] = $c;
                    return $estados;
            }
            // O bloco da etapa salva e a etapa avança; a última pesagem fica
            // parada na conferência (a mesma etapa) até o clique de fechar.
            $seguinte = $prox($etapa);
            if ($seguinte !== 'FINALIZADO') {
                $c['etapa'] = $seguinte;
            }
            $estados[] = $c;
        }
        return $estados;
    }

    /**
     * Percorre o fluxo inteiro. Para no primeiro envio que não for 2xx e o
     * devolve; senão devolve a resposta do FINALIZADO. $c volta no último estado.
     */
    public function percorrer(array &$c, float $pbt, float $tara, array $opc = [], ?array $parametros = null): Resposta
    {
        $resp = null;
        foreach (static::passos($c, $pbt, $tara, $opc) as $estado) {
            // Envia $c (nao $estado): so assim o `versao` que enviar() atualiza
            // por referencia continua acompanhando o aparelho entre os passos.
            $c = $estado;
            $resp = $this->enviar($c, $parametros);
            if (!$resp->ok()) {
                return $resp;
            }
        }
        return $resp;
    }

    /** Até a etapa ANTERIOR ao FINALIZADO (pra disparar o fechamento em paralelo). */
    public function preparar(array &$c, float $pbt, float $tara, array $opc = [], ?array $parametros = null): ?Resposta
    {
        $estados = static::passos($c, $pbt, $tara, $opc);
        $final = array_pop($estados);
        foreach ($estados as $estado) {
            $c = $estado;
            $resp = $this->enviar($c, $parametros);
            if (!$resp->ok()) {
                return $resp;
            }
        }
        $c = array_replace($final, ['versao' => $c['versao'] ?? null]);
        return null;
    }

    // --------------------------------------------------- leitura do banco

    /** Carga como está gravada: linha, pontos, leituras e extrato automático ativo. */
    public static function banco(string $uuid): ?object
    {
        $c = DB::table('tblcarga')->where('uuid', $uuid)->first();
        if (!$c) {
            return null;
        }
        $c->pontos = DB::table('tblcargaponto')->where('codcarga', $c->codcarga)->orderBy('codcargaponto')->get()->all();
        $c->classificacao = DB::table('tblcargaclassificacao')->where('codcarga', $c->codcarga)->get()->all();
        $c->movimentos = DB::table('tblmovimentograo')->where('codcarga', $c->codcarga)->where('manual', false)->whereNull('inativo')->orderBy('codmovimentograo')->get()->all();
        return $c;
    }

    public static function saldo(string $contatipo, int $cod, ?int $codsafra = null): float
    {
        return (float) DB::table('tblmovimentograo')
            ->where('contatipo', $contatipo)
            ->where(static::CONTA[$contatipo], $cod)
            ->whereNull('inativo')
            ->when($codsafra, fn ($q) => $q->where('codsafra', $codsafra))
            ->sum('liquido');
    }

    public static function contarCargas(string $uuid): int
    {
        return DB::table('tblcarga')->where('uuid', $uuid)->count();
    }

    /** Pontos da carga (do banco) no formato da Referencia::movimentos. */
    public static function pontosReferencia(object $carga): array
    {
        return array_map(fn ($p) => [
            'papel' => $p->papel,
            'contatipo' => $p->contatipo,
            'conta' => $p->codplantio ?? $p->codunidadearmazenadora ?? $p->codcontrato,
            'peso' => (float) $p->liquido,
        ], $carga->pontos);
    }
}
