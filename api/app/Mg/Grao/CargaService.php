<?php

namespace Mg\Grao;

use Mg\MgService;
use Mg\Safra\Safra;
use Mg\Cultura\TabelaDesconto;
use Mg\Contrato\Contrato;
use Mg\Veiculo\Veiculo;
use Mg\Pessoa\Pessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Carga = documento operacional do patio (offline-first, upsert por uuid). O
 * servidor e a autoridade: recalcula pesos/descontos, valida o fechamento e
 * GERA o extrato (tblmovimentograo) a partir dos pontos (idempotente).
 */
class CargaService extends MgService
{
    const WITH = [
        'Safra.Cultura',
        'Veiculo',
        'PessoaMotorista',
        'CargaPontoS.Plantio.Talhao',
        'CargaPontoS.Plantio.Variedade',
        'CargaPontoS.UnidadeArmazenadora',
        'CargaPontoS.Contrato.Pessoa',
    ];

    // Etapas aceitas (uniao dos fluxos). A ordem por sentido fica no front:
    //  ENTRADA: PBT -> CLASSIFICACAO -> TARA -> FINALIZADO
    //  SAIDA:   TARA -> PBT -> FISCAL -> FINALIZADO
    //  TRANSFERENCIA: PBT -> TARA -> FINALIZADO
    const ETAPAS = ['PBT', 'TARA', 'CLASSIFICACAO', 'FISCAL', 'FINALIZADO'];
    const SENTIDOS = ['ENTRADA', 'SAIDA', 'TRANSFERENCIA'];
    const CONTATIPOS = ['PLANTIO', 'UNIDADE', 'CONTRATO'];
    const ETAPA_FINAL = 'FINALIZADO';

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Carga::query()->with(static::WITH);

        if (!empty($filter['codcarga'])) {
            $qry->where('codcarga', $filter['codcarga']);
        }
        if (!empty($filter['uuid'])) {
            $qry->where('uuid', $filter['uuid']);
        }
        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }
        if (!empty($filter['sentido'])) {
            $qry->where('sentido', $filter['sentido']);
        }
        if (!empty($filter['etapa'])) {
            $qry->where('etapa', $filter['etapa']);
        }
        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['-data']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Upsert por uuid (offline). Grava a carga + os pontos (origens/destinos),
     * recalcula pesos/descontos, valida o fechamento e regera o extrato.
     */
    public static function sincronizar(array $data): Carga
    {
        return DB::transaction(function () use ($data) {
            $carga = Carga::firstOrNew(['uuid' => $data['uuid']]);
            $carga->fill($data);
            static::snapshotCaminhaoMotorista($carga);
            static::calcular($carga);
            $carga->save();
            static::sincronizarPontos($carga, $data['pontos'] ?? []);
            $carga->load('CargaPontoS');
            static::validar($carga);
            static::gerarMovimento($carga);
            return $carga->fresh(static::WITH);
        });
    }

    /**
     * Mantem o snapshot textual (placa/motorista) coerente com o cadastro
     * quando a carga vem com a FK mas sem o texto. Preserva o texto livre.
     */
    protected static function snapshotCaminhaoMotorista(Carga $carga): void
    {
        if (!empty($carga->codveiculo) && empty($carga->placa)) {
            $carga->placa = optional(Veiculo::find($carga->codveiculo))->placa;
        }
        if (!empty($carga->codpessoamotorista) && empty($carga->motorista)) {
            $pessoa = Pessoa::find($carga->codpessoamotorista);
            $nome = $pessoa ? ($pessoa->fantasia ?: $pessoa->pessoa) : null;
            $carga->motorista = $nome ? mb_substr($nome, 0, 60) : null;
        }
    }

    /** Substitui os pontos da carga pelo conjunto informado. */
    protected static function sincronizarPontos(Carga $carga, array $pontos): void
    {
        CargaPonto::where('codcarga', $carga->codcarga)->delete();
        foreach ($pontos as $p) {
            $tipo = $p['contatipo'] ?? null;
            $papel = $p['papel'] ?? null;
            if (!in_array($tipo, static::CONTATIPOS, true) || !in_array($papel, ['ORIGEM', 'DESTINO'], true)) {
                continue;
            }
            // Ponto sem conta valida e ignorado (linha vazia da UI).
            $conta = static::contaDoPonto($tipo, $p);
            if ($conta === null) {
                continue;
            }
            $cp = new CargaPonto();
            $cp->codcarga = $carga->codcarga;
            $cp->papel = $papel;
            $cp->contatipo = $tipo;
            $cp->codplantio = $tipo === 'PLANTIO' ? $conta : null;
            $cp->codunidadearmazenadora = $tipo === 'UNIDADE' ? $conta : null;
            $cp->codcontrato = $tipo === 'CONTRATO' ? $conta : null;
            $cp->liquido = $p['liquido'] ?? null;
            $cp->numeronf = $p['numeronf'] ?? null;
            $cp->valornf = $p['valornf'] ?? null;
            $cp->chavenf = $p['chavenf'] ?? null;
            $cp->save();
        }
    }

    /** Codigo da conta do ponto conforme o tipo (UNIDADE aceita null = silo? nao). */
    protected static function contaDoPonto(string $tipo, array $p): ?int
    {
        return match ($tipo) {
            'PLANTIO' => $p['codplantio'] ?? null,
            'UNIDADE' => $p['codunidadearmazenadora'] ?? null,
            'CONTRATO' => $p['codcontrato'] ?? null,
            default => null,
        };
    }

    /**
     * bruto = pbt - tara; descontos (kg) por faixa da cultura da safra;
     * desconto = soma; liquido = bruto - desconto. Tudo null enquanto faltam os
     * pesos (carga em etapa inicial do patio).
     */
    public static function calcular(Carga $carga): void
    {
        if ($carga->pbt !== null && $carga->tara !== null) {
            $carga->bruto = round(((float) $carga->pbt) - ((float) $carga->tara), 3);
        } else {
            $carga->bruto = null;
        }

        $bruto = $carga->bruto;
        if ($bruto === null) {
            $carga->descontoumidade = null;
            $carga->descontoimpureza = null;
            $carga->descontoavariados = null;
            $carga->desconto = null;
            $carga->liquido = null;
            return;
        }

        $codcultura = optional(Safra::find($carga->codsafra))->codcultura;
        $carga->descontoumidade = static::descontoKg($codcultura, 'UMIDADE', $carga->umidade, $bruto);
        $carga->descontoimpureza = static::descontoKg($codcultura, 'IMPUREZA', $carga->impureza, $bruto);
        $carga->descontoavariados = static::descontoKg($codcultura, 'AVARIADOS', $carga->avariados, $bruto);

        $carga->desconto = round(
            ((float) $carga->descontoumidade)
                + ((float) $carga->descontoimpureza)
                + ((float) $carga->descontoavariados),
            3
        );
        $carga->liquido = round($bruto - (float) $carga->desconto, 3);
    }

    /**
     * Desconto em kg de um tipo a partir da faixa que contem a leitura, sobre o
     * BRUTO (grao pos-tara). Sem leitura => null; sem faixa => 0.
     */
    public static function descontoKg(?int $codcultura, string $tipo, $leitura, float $bruto): ?float
    {
        if (empty($codcultura) || $leitura === null || $leitura === '') {
            return null;
        }
        $faixa = TabelaDesconto::ativo()
            ->where('codcultura', $codcultura)
            ->where('tipo', $tipo)
            ->where('faixainicio', '<=', $leitura)
            ->where('faixafim', '>=', $leitura)
            ->orderBy('faixainicio', 'desc')
            ->first();
        if (!$faixa) {
            return 0.0;
        }
        return round($bruto * ((float) $faixa->percentualdesconto / 100), 3);
    }

    /**
     * Validacoes (autoridade do servidor): fechamento do rateio nas etapas
     * finais + over-load de contrato de venda. So cobra fechamento no FINALIZADO
     * (antes disso o kanban aceita parcial).
     */
    protected static function validar(Carga $carga): void
    {
        static::validarOverloadContrato($carga);

        if ($carga->etapa !== static::ETAPA_FINAL) {
            return;
        }
        $liq = (float) $carga->liquido;
        if ($liq <= 0) {
            throw ValidationException::withMessages([
                'liquido' => 'Peso liquido invalido (pbt - tara - desconto deve ser > 0) para finalizar.',
            ]);
        }
        foreach (['ORIGEM', 'DESTINO'] as $papel) {
            $pontos = $carga->CargaPontoS->where('papel', $papel);
            if ($pontos->isEmpty()) {
                throw ValidationException::withMessages([
                    'pontos' => "Informe ao menos uma " . ($papel === 'ORIGEM' ? 'origem' : 'destino')
                        . " para finalizar a carga.",
                ]);
            }
            $soma = (float) $pontos->sum('liquido');
            if (abs($soma - $liq) > 1) {
                throw ValidationException::withMessages([
                    'pontos' => "A soma das " . ($papel === 'ORIGEM' ? 'origens' : 'destinos')
                        . " (" . round($soma) . " kg) deve fechar com o liquido (" . round($liq) . " kg).",
                ]);
            }
        }
    }

    /**
     * Bloqueio de over-load: destino CONTRATO (venda) nao pode levar o total
     * entregue acima do contratado. Contrato volumeemaberto (rapa-silo) pula.
     */
    protected static function validarOverloadContrato(Carga $carga): void
    {
        $kgPorContrato = [];
        foreach ($carga->CargaPontoS as $p) {
            if ($p->contatipo === 'CONTRATO' && $p->papel === 'DESTINO' && $p->codcontrato) {
                $kgPorContrato[$p->codcontrato] = ($kgPorContrato[$p->codcontrato] ?? 0) + (float) $p->liquido;
            }
        }
        if (!$kgPorContrato) {
            return;
        }
        $contratos = Contrato::with('Cultura')
            ->whereIn('codcontrato', array_keys($kgPorContrato))
            ->get()
            ->keyBy('codcontrato');

        foreach ($kgPorContrato as $cod => $estaCargaKg) {
            $contrato = $contratos->get($cod);
            if (!$contrato || $contrato->volumeemaberto) {
                continue;
            }
            $pesosaca = (float) ($contrato->Cultura->pesosaca ?? 60) ?: 60;
            $contratadokg = (float) $contrato->quantidade * $pesosaca;
            // entregue por OUTRAS cargas (extrato; exclui a propria).
            $jaOutros = (float) MovimentoGrao::where('contatipo', 'CONTRATO')
                ->where('codcontrato', $cod)
                ->where('papel', 'DESTINO')
                ->when($carga->codcarga, fn ($q) => $q->where('codcarga', '!=', $carga->codcarga))
                ->sum('liquido');
            if ($jaOutros + $estaCargaKg > $contratadokg + 1) {
                $saldo = max(0, $contratadokg - $jaOutros);
                throw ValidationException::withMessages([
                    'pontos' => "Contrato {$contrato->contrato}: carregamento excede o contratado ("
                        . round($contratadokg) . " kg). Saldo disponivel: " . round($saldo) . " kg.",
                ]);
            }
        }
    }

    // ===================== Geracao do extrato (idempotente) ==============

    /**
     * (Re)gera as linhas AUTOMATICAS do extrato desta carga a partir dos pontos.
     * Idempotente: apaga as automaticas e recria (manuais nunca sao tocadas). So
     * realiza carga ATIVA + FINALIZADA + com liquido; senao apenas limpa.
     */
    public static function gerarMovimento(Carga $carga): void
    {
        MovimentoGrao::where('codcarga', $carga->codcarga)->where('manual', false)->delete();

        if ($carga->inativo !== null) {
            return;
        }
        if ($carga->etapa !== static::ETAPA_FINAL) {
            return;
        }
        $liquido = (float) $carga->liquido;
        $bruto = (float) $carga->bruto;
        if ($liquido <= 0) {
            return;
        }

        $carga->loadMissing('CargaPontoS');
        foreach (['ORIGEM', 'DESTINO'] as $papel) {
            $pontos = $carga->CargaPontoS->where('papel', $papel)->values();
            $pesos = $pontos->map(fn ($p) => (float) $p->liquido)->all();
            $somaPesos = array_sum($pesos);
            if ($somaPesos <= 0) {
                continue;
            }
            $liqShares = static::ratear($liquido, $pesos);
            $brutoShares = static::ratear($bruto, $pesos);
            foreach ($pontos as $i => $p) {
                $sinal = static::sinal($p->contatipo, $papel);
                $liq = round($liqShares[$i] * $sinal, 3);
                $bru = round($brutoShares[$i] * $sinal, 3);
                $des = round($bru - $liq, 3);
                MovimentoGrao::create([
                    'codcarga' => $carga->codcarga,
                    'manual' => false,
                    'codsafra' => $carga->codsafra,
                    'data' => $carga->data,
                    'papel' => $papel,
                    'contatipo' => $p->contatipo,
                    'codplantio' => $p->codplantio,
                    'codunidadearmazenadora' => $p->codunidadearmazenadora,
                    'codcontrato' => $p->codcontrato,
                    'bruto' => $bru,
                    'desconto' => $des,
                    'liquido' => $liq,
                ]);
            }
        }
    }

    /**
     * Sinal do saldo: UNIDADE e a unica conta-saldo (+entrada/-saida); PLANTIO
     * (producao) e CONTRATO (entregue/recebido) sao contadores (sempre +).
     */
    protected static function sinal(string $contatipo, string $papel): int
    {
        if ($contatipo === 'UNIDADE') {
            return $papel === 'DESTINO' ? 1 : -1;
        }
        return 1;
    }

    /** Rateia $total proporcional aos $pesos; o resto de arredondamento vai no ultimo. */
    protected static function ratear(float $total, array $pesos): array
    {
        $soma = array_sum($pesos);
        $n = count($pesos);
        if ($soma <= 0 || $n === 0) {
            return array_fill(0, $n, 0.0);
        }
        $res = [];
        $acc = 0.0;
        for ($i = 0; $i < $n; $i++) {
            if ($i < $n - 1) {
                $v = round($total * $pesos[$i] / $soma, 3);
                $res[$i] = $v;
                $acc += $v;
            } else {
                $res[$i] = round($total - $acc, 3);
            }
        }
        return $res;
    }

    /**
     * Recalcula o extrato de um conjunto de cargas (idempotente). Util pra
     * "recalcular" safra/contrato/unidade apos ajuste de cadastro ou faixa.
     * Retorna a quantidade de cargas reprocessadas.
     */
    public static function recalcular(array $filter = []): int
    {
        return DB::transaction(function () use ($filter) {
            $qry = Carga::query()->with('CargaPontoS');
            if (!empty($filter['codsafra'])) {
                $qry->where('codsafra', $filter['codsafra']);
            }
            if (!empty($filter['codcarga'])) {
                $qry->where('codcarga', $filter['codcarga']);
            }
            if (!empty($filter['codcontrato'])) {
                $qry->whereHas('CargaPontoS', fn ($q) => $q->where('codcontrato', $filter['codcontrato']));
            }
            if (!empty($filter['codunidadearmazenadora'])) {
                $qry->whereHas(
                    'CargaPontoS',
                    fn ($q) => $q->where('codunidadearmazenadora', $filter['codunidadearmazenadora'])
                );
            }
            $n = 0;
            $qry->chunkById(200, function ($cargas) use (&$n) {
                foreach ($cargas as $carga) {
                    static::calcular($carga);
                    $carga->saveQuietly();
                    static::gerarMovimento($carga);
                    $n++;
                }
            }, 'codcarga');
            return $n;
        });
    }

    public static function inativar($model, $date = null)
    {
        $model = parent::inativar($model, $date);
        // Carga inativada some do extrato (estorno) — mantem os saldos coerentes.
        static::gerarMovimento($model->fresh('CargaPontoS'));
        return $model;
    }

    public static function ativar($model)
    {
        $model = parent::ativar($model);
        static::gerarMovimento($model->fresh('CargaPontoS'));
        return $model;
    }
}
