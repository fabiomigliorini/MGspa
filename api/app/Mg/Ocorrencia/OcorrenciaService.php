<?php

namespace Mg\Ocorrencia;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Auditoria\Auditoria;
use Mg\Auditoria\AuditoriaService;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioParcelaService;
use Mg\Negocio\NegocioService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Pdv\Pdv;
use Mg\Portador\PortadorMovimento;

/**
 * Livro de ocorrencias (TASK-205): o que o gerente confere no fim do dia.
 *
 * Tudo nasce no servidor e so' para PDV monitorado (tblpdv.monitoramento),
 * registro criado a partir da data. O fato (o que mudou, antes/depois,
 * justificativa) vai para a auditoria; a ocorrencia amarra as auditorias
 * (N:N) e guarda descricao, valor e a conferencia. Uma ocorrencia por
 * registro e tipo (uk_tblocorrencia): as do negocio (1 a 8) juntam os itens
 * daquele tipo; esquecido e desconto acima sao estado, sem auditoria.
 * O que se olha no PDV esta' em OcorrenciaPdvService.
 */
class OcorrenciaService
{
    // do negocio, uma por tipo amarrando as auditorias dos itens
    const TIPO_ITEM_EXCLUIDO = 1;
    const TIPO_QUANTIDADE_DIMINUIDA = 2;
    const TIPO_PRECO_ABAIXO = 3;
    const TIPO_PRECO_ACIMA = 4;
    const TIPO_VALE_EXCLUIDO = 5;
    const TIPO_PAGAMENTO_APAGADO = 6;
    const TIPO_PARCELA_APAGADA = 7;
    const TIPO_SEM_FINANCEIRO = 8;

    const TIPO_NEGOCIO_CANCELADO = 10;
    const TIPO_PAGAMENTO_ESTORNADO = 11;
    const TIPO_VALE_ESTORNADO = 12;
    const TIPO_DESCONTO_ACIMA = 13;
    const TIPO_NEGOCIO_ESQUECIDO = 14;

    // correcoes de lancamento (TASK-204), uma por correcao
    const TIPO_DATA_ALTERADA = 15;
    const TIPO_DATA_CANCELAMENTO_ALTERADA = 16;
    const TIPO_CORRIGIDO_CONFERENCIA = 17;
    const TIPO_REGISTRO_INDEVIDO = 18;
    const TIPO_INCLUIDO_CONFERENCIA = 19;

    const TIPOS = [
        self::TIPO_ITEM_EXCLUIDO => 'Item excluído',
        self::TIPO_QUANTIDADE_DIMINUIDA => 'Quantidade diminuída',
        self::TIPO_PRECO_ABAIXO => 'Preço abaixo do cadastro',
        self::TIPO_PRECO_ACIMA => 'Preço acima do cadastro',
        self::TIPO_VALE_EXCLUIDO => 'Vale compras excluído',
        self::TIPO_PAGAMENTO_APAGADO => 'Pagamento apagado',
        self::TIPO_PARCELA_APAGADA => 'Parcela a prazo apagada',
        self::TIPO_SEM_FINANCEIRO => 'Saída sem financeiro',
        self::TIPO_NEGOCIO_CANCELADO => 'Negócio cancelado',
        self::TIPO_PAGAMENTO_ESTORNADO => 'Pagamento estornado',
        self::TIPO_VALE_ESTORNADO => 'Vale estornado',
        self::TIPO_DESCONTO_ACIMA => 'Desconto acima do permitido',
        self::TIPO_NEGOCIO_ESQUECIDO => 'Negócio esquecido',
        self::TIPO_DATA_ALTERADA => 'Data alterada',
        self::TIPO_DATA_CANCELAMENTO_ALTERADA => 'Data do cancelamento alterada',
        self::TIPO_CORRIGIDO_CONFERENCIA => 'Corrigido na conferência',
        self::TIPO_REGISTRO_INDEVIDO => 'Registro indevido',
        self::TIPO_INCLUIDO_CONFERENCIA => 'Incluído na conferência',
    ];

    // tipo da auditoria da TASK-204 => tipo da ocorrencia
    const CORRECOES = [
        AuditoriaService::TIPO_DATA_ALTERADA => self::TIPO_DATA_ALTERADA,
        AuditoriaService::TIPO_DATA_CANCELAMENTO_ALTERADA => self::TIPO_DATA_CANCELAMENTO_ALTERADA,
        AuditoriaService::TIPO_CORRIGIDO_CONFERENCIA => self::TIPO_CORRIGIDO_CONFERENCIA,
        AuditoriaService::TIPO_REGISTRO_INDEVIDO => self::TIPO_REGISTRO_INDEVIDO,
        AuditoriaService::TIPO_INCLUIDO_CONFERENCIA => self::TIPO_INCLUIDO_CONFERENCIA,
    ];

    // PDV monitorado e registro criado a partir da data
    public static function monitorado(?Pdv $pdv, $criacao = null): bool
    {
        if (empty($pdv) || empty($pdv->monitoramento)) {
            return false;
        }
        $criacao = $criacao ? Carbon::parse($criacao) : Carbon::now();
        return $criacao->gte($pdv->monitoramento->copy()->startOfDay());
    }

    // A ocorrencia e' da loja do PDV (quem confere e' o gerente de la'),
    // mesmo quando o estoque do negocio e' de outra filial
    public static function filial(?Pdv $pdv, $codfilial): ?int
    {
        return $pdv->codfilial ?? $codfilial;
    }

    // Uma vez so' por registro e tipo; com auditorias, amarra
    public static function registrar(array $dados, array $auditorias = []): Ocorrencia
    {
        $oc = Ocorrencia::firstOrCreate([
            'tipo' => $dados['tipo'],
            'tabela' => $dados['tabela'],
            'codigo' => $dados['codigo'],
        ], $dados);
        static::amarrar($oc, $auditorias);
        return $oc;
    }

    public static function amarrar(Ocorrencia $oc, array $auditorias): void
    {
        foreach ($auditorias as $aud) {
            OcorrenciaAuditoria::firstOrCreate([
                'codocorrencia' => $oc->codocorrencia,
                'codauditoria' => $aud->codauditoria,
            ]);
        }
    }

    // Ocorrencia do negocio (tipos 1 a 8): amarra as auditorias e refaz
    // descricao e valor a partir de todas as que ela tem
    public static function doNegocio(Negocio $negocio, int $tipo, array $auditorias, ?int $codusuario): ?Ocorrencia
    {
        if (empty($auditorias)) {
            return null;
        }
        $oc = Ocorrencia::firstOrNew([
            'tipo' => $tipo,
            'tabela' => 'tblnegocio',
            'codigo' => $negocio->codnegocio,
        ]);
        $oc->fill([
            'codnegocio' => $negocio->codnegocio,
            'codpdv' => $negocio->codpdv,
            'codfilial' => static::filial($negocio->Pdv, $negocio->codfilial),
            'codusuario' => $codusuario,
        ]);
        $oc->descricao = $oc->descricao ?? static::TIPOS[$tipo];
        $oc->save();
        static::amarrar($oc, $auditorias);
        static::resumir($oc);
        return $oc;
    }

    // descricao = o que cada auditoria amarrada diz; valor = soma
    private static function resumir(Ocorrencia $oc): void
    {
        $linhas = static::linhas($oc->AuditoriaS()->get());
        $valor = round(array_sum(array_column($linhas, 'valor')), 2);
        $descricao = implode('; ', array_column($linhas, 'texto')) . ' — R$ ' . formataNumero($valor);
        $oc->descricao = mb_strimwidth($descricao, 0, 300, '…');
        $oc->valor = max($valor, 0);
        $oc->save();
    }

    // [texto, valor] de cada auditoria de item do negocio
    private static function linhas(Collection $auds): array
    {
        $itens = static::itensDoNegocio(
            $auds->where('tabela', 'tblnegocioprodutobarra')->pluck('codigo')->all()
        );
        $vales = static::valesDoNegocio(
            $auds->where('tabela', 'tblnegociovale')->pluck('codigo')->all()
        );
        $ret = [];
        foreach ($auds as $aud) {
            $item = $itens[$aud->codigo] ?? null;
            $antes = $aud->antes ?? [];
            $depois = $aud->depois ?? [];
            switch ($aud->tipo) {
                case AuditoriaService::TIPO_ITEM_EXCLUIDO:
                    $ret[] = [
                        'texto' => static::quantidade($item->quantidade ?? 0) . '× ' . ($item->produto ?? '?'),
                        'valor' => (float) ($item->valortotal ?? 0),
                    ];
                    break;
                case AuditoriaService::TIPO_QUANTIDADE_ALTERADA:
                    $ret[] = [
                        'texto' => ($item->produto ?? '?') . ' de ' . static::quantidade($antes['quantidade'] ?? 0)
                            . ' para ' . static::quantidade($depois['quantidade'] ?? 0),
                        'valor' => (float) ($antes['valortotal'] ?? 0) - (float) ($depois['valortotal'] ?? 0),
                    ];
                    break;
                case AuditoriaService::TIPO_PRECO_CADASTRO:
                    $quantidade = (float) ($item->quantidade ?? 0);
                    $cadastro = (float) ($antes['valorunitario'] ?? 0);
                    $praticado = (float) ($depois['valorunitario'] ?? 0);
                    $ret[] = [
                        'texto' => static::quantidade($quantidade) . '× ' . ($item->produto ?? '?') . ' a R$ '
                            . formataNumero($praticado) . ' (cadastro R$ ' . formataNumero($cadastro) . ')',
                        'valor' => round(abs($cadastro - $praticado) * $quantidade, 2),
                    ];
                    break;
                case AuditoriaService::TIPO_VALE_EXCLUIDO:
                    $vale = $vales[$aud->codigo] ?? null;
                    $ret[] = [
                        'texto' => 'Vale de R$ ' . formataNumero($vale->valortotal ?? 0)
                            . (!empty($vale->favorecido) ? " para {$vale->favorecido}" : ''),
                        'valor' => (float) ($vale->valortotal ?? 0),
                    ];
                    break;
                case AuditoriaService::TIPO_PAGAMENTO_APAGADO:
                    $ret[] = [
                        'texto' => 'R$ ' . formataNumero($antes['total'] ?? 0) . ' em '
                            . (PagamentoService::MEIOS[$antes['meio'] ?? null] ?? ($antes['meio'] ?? '?')),
                        'valor' => (float) ($antes['total'] ?? 0),
                    ];
                    break;
                case AuditoriaService::TIPO_PARCELA_APAGADA:
                    $ret[] = [
                        'texto' => (NegocioParcelaService::CONDICOES[$antes['condicao'] ?? null] ?? ($antes['condicao'] ?? '?'))
                            . ' ' . ($antes['numero'] ?? 1) . ' de R$ ' . formataNumero($antes['valor'] ?? 0)
                            . (!empty($antes['vencimento']) ? ' venc. ' . Carbon::parse($antes['vencimento'])->format('d/m/Y') : ''),
                        'valor' => (float) ($antes['valor'] ?? 0),
                    ];
                    break;
            }
        }
        return $ret;
    }

    // nome do produto como o caixa ve' (sql com i = tblnegocioprodutobarra)
    const SQL_PRODUTO = "
        trim(p.produto || coalesce(' ' || pv.variacao, '')
            || coalesce(' C/' || to_char(pe.quantidade, 'FM999999990'), ''))
    ";
    const SQL_JOIN_PRODUTO = '
        inner join tblprodutobarra pb on (pb.codprodutobarra = i.codprodutobarra)
        inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
        inner join tblproduto p on (p.codproduto = pv.codproduto)
        left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
    ';

    private static function itensDoNegocio(array $codigos): array
    {
        if (empty($codigos)) {
            return [];
        }
        $regs = DB::select('
            select i.codnegocioprodutobarra, i.quantidade, i.valortotal, ' . static::SQL_PRODUTO . ' as produto
            from tblnegocioprodutobarra i
            ' . static::SQL_JOIN_PRODUTO . '
            where i.codnegocioprodutobarra in (' . implode(',', array_map('intval', array_unique($codigos))) . ')
        ');
        return collect($regs)->keyBy('codnegocioprodutobarra')->all();
    }

    private static function valesDoNegocio(array $codigos): array
    {
        if (empty($codigos)) {
            return [];
        }
        $regs = DB::select('
            select v.codnegociovale, v.valortotal, coalesce(v.aluno, pf.fantasia) as favorecido
            from tblnegociovale v
            left join tblpessoa pf on (pf.codpessoa = v.codpessoafavorecido)
            where v.codnegociovale in (' . implode(',', array_map('intval', array_unique($codigos))) . ')
        ');
        return collect($regs)->keyBy('codnegociovale')->all();
    }

    // 1 un, 2,5 kg
    public static function quantidade($q): string
    {
        $q = (float) $q;
        return formataNumero($q, floor($q) == $q ? 0 : 3);
    }

    // ---- negocio cancelado, estorno ----

    // Antes de cancelar: o que o negocio tinha (os pagamentos sao
    // cancelados em cascata e nao geram ocorrencia propria)
    public static function fotoCancelamento(Negocio $negocio): array
    {
        return [
            'codnegociostatus' => $negocio->codnegociostatus,
            'valortotal' => (float) $negocio->valortotal,
            'pagamentos' => $negocio->PagamentoS()
                ->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)
                ->orderBy('codpagamento')
                ->get()
                ->map(fn ($pag) => [
                    'codpagamento' => $pag->codpagamento,
                    'meio' => $pag->meio,
                    'estado' => $pag->estado,
                    'total' => (float) $pag->total,
                    'autorizacao' => $pag->autorizacao,
                ])
                ->all(),
        ];
    }

    public static function negocioCancelado(Negocio $negocio, array $foto, string $justificativa): ?Ocorrencia
    {
        if (!static::monitorado($negocio->Pdv, $negocio->criacao)) {
            return null;
        }
        if ($foto['valortotal'] <= 0 && empty($foto['pagamentos'])) {
            return null;
        }
        $aud = AuditoriaService::registrar(
            'tblnegocio',
            $negocio->codnegocio,
            AuditoriaService::TIPO_NEGOCIO_CANCELADO,
            $foto,
            ['codnegociostatus' => $negocio->codnegociostatus],
            $justificativa
        );
        $status = NegocioService::CODNEGOCIOSTATUS_DESCRICAO[$foto['codnegociostatus']] ?? $foto['codnegociostatus'];
        $meios = collect($foto['pagamentos'])
            ->map(fn ($p) => PagamentoService::MEIOS[$p['meio']] ?? $p['meio'])
            ->unique()
            ->implode(', ');
        $descricao = "Cancelou negócio {$status} de R$ " . formataNumero($foto['valortotal']);
        if (!empty($meios)) {
            $descricao .= " pago em {$meios}";
        }
        return static::registrar([
            'tipo' => static::TIPO_NEGOCIO_CANCELADO,
            'tabela' => 'tblnegocio',
            'codigo' => $negocio->codnegocio,
            'codnegocio' => $negocio->codnegocio,
            'codpdv' => $negocio->codpdv,
            'codfilial' => static::filial($negocio->Pdv, $negocio->codfilial),
            'codusuario' => Auth::user()->codusuario ?? null,
            'descricao' => mb_substr($descricao, 0, 300),
            'valor' => $foto['valortotal'],
        ], [$aud]);
    }

    // Estorno de recebimento (titulo, vale colaborador, adiantamento). O
    // pagamento de venda e' cancelado pelo negocio e nao passa por aqui.
    public static function pagamentoEstornado(Pagamento $pag, bool $vale, string $justificativa): ?Ocorrencia
    {
        if (!static::monitorado($pag->Pdv, $pag->criacao)) {
            return null;
        }
        $aud = AuditoriaService::registrar(
            'tblpagamento',
            $pag->codpagamento,
            AuditoriaService::TIPO_ESTORNADO,
            ['estado' => PagamentoService::ESTADO_EFETIVADO],
            ['estado' => $pag->estado],
            $justificativa
        );
        $meio = PagamentoService::MEIOS[$pag->meio] ?? $pag->meio;
        $tipo = $vale ? static::TIPO_VALE_ESTORNADO : static::TIPO_PAGAMENTO_ESTORNADO;
        $quem = $pag->Pessoa->fantasia ?? null;
        $descricao = ($vale ? 'Estornou vale' : 'Estornou recebimento') . " de R$ " . formataNumero($pag->total) . " em {$meio}";
        if (!empty($quem)) {
            $descricao .= " de {$quem}";
        }
        return static::registrar([
            'tipo' => $tipo,
            'tabela' => 'tblpagamento',
            'codigo' => $pag->codpagamento,
            'codnegocio' => $pag->codnegocio,
            'codpdv' => $pag->codpdv,
            'codfilial' => static::filial($pag->Pdv, $pag->codfilial),
            'codusuario' => Auth::user()->codusuario ?? null,
            'descricao' => mb_substr($descricao, 0, 300),
            'valor' => abs((float) $pag->total),
        ], [$aud]);
    }

    // ---- correcoes de lancamento (TASK-204) ----

    // Uma correcao (as auditorias que ela gravou: a transferencia grava as
    // duas pontas) vira uma ocorrencia, se o lancamento e' de PDV monitorado
    public static function correcao(array $auditorias): ?Ocorrencia
    {
        $auditorias = array_values(array_filter($auditorias));
        if (empty($auditorias) || !isset(static::CORRECOES[$auditorias[0]->tipo])) {
            return null;
        }
        foreach ($auditorias as $aud) {
            [$pdv, $registro] = static::pdvDoLancamento($aud);
            if ($pdv && static::monitorado($pdv, $registro->criacao)) {
                break;
            }
            $pdv = null;
        }
        if (!$pdv) {
            return null;
        }
        $aud = $auditorias[0];
        $ehPagamento = $registro instanceof Pagamento;
        $valor = abs((float) ($ehPagamento ? $registro->total : $registro->valor));
        return static::registrar([
            'tipo' => static::CORRECOES[$aud->tipo],
            'tabela' => 'tblauditoria',
            'codigo' => $aud->codauditoria,
            'codnegocio' => $ehPagamento ? $registro->codnegocio : null,
            'codpdv' => $pdv->codpdv,
            'codfilial' => static::filial($pdv, $registro->codfilial ?? null),
            'codusuario' => Auth::user()->codusuario ?? null,
            'descricao' => mb_strimwidth(static::descreverCorrecao($aud, $registro, $valor), 0, 300, '…'),
            'valor' => $valor,
        ], $auditorias);
    }

    // [Pdv, registro]: o pagamento tem o PDV; o movimento do portador cai na
    // gaveta de um PDV
    private static function pdvDoLancamento(Auditoria $aud): array
    {
        if ($aud->tabela == 'tblpagamento') {
            $pag = Pagamento::find($aud->codigo);
            return [$pag?->Pdv, $pag];
        }
        if ($aud->tabela == 'tblportadormovimento') {
            $mov = PortadorMovimento::find($aud->codigo);
            $pdv = $mov ? Pdv::where('codportador', $mov->codportador)->whereNotNull('monitoramento')->first() : null;
            return [$pdv, $mov];
        }
        return [null, null];
    }

    private static function descreverCorrecao(Auditoria $aud, $registro, float $valor): string
    {
        $quanto = 'R$ ' . formataNumero($valor);
        if ($registro instanceof Pagamento) {
            $quanto .= ' em ' . (PagamentoService::MEIOS[$registro->meio] ?? $registro->meio);
        }
        $data = fn ($v) => $v ? Carbon::parse($v)->format('d/m H:i') : '—';
        switch ($aud->tipo) {
            case AuditoriaService::TIPO_DATA_ALTERADA:
                return "Data de {$quanto} de " . $data($aud->antes['transacao'] ?? null)
                    . ' para ' . $data($aud->depois['transacao'] ?? null);
            case AuditoriaService::TIPO_DATA_CANCELAMENTO_ALTERADA:
                return "Data do cancelamento de {$quanto} de " . $data($aud->antes['cancelamento'] ?? null)
                    . ' para ' . $data($aud->depois['cancelamento'] ?? null);
            case AuditoriaService::TIPO_CORRIGIDO_CONFERENCIA:
                $campos = collect(array_keys(($aud->antes ?? []) + ($aud->depois ?? [])))
                    ->map(fn ($c) => "{$c} " . static::valorCampo($c, $aud->antes[$c] ?? null)
                        . ' → ' . static::valorCampo($c, $aud->depois[$c] ?? null))
                    ->implode(', ');
                return "Corrigiu {$quanto}: {$campos}";
            case AuditoriaService::TIPO_REGISTRO_INDEVIDO:
                return "Marcou como indevido {$quanto}";
            case AuditoriaService::TIPO_INCLUIDO_CONFERENCIA:
                return "Incluiu {$quanto} na conferência";
        }
        return AuditoriaService::TIPOS[$aud->tipo] ?? (string) $aud->tipo;
    }

    private static function valorCampo(string $campo, $valor): string
    {
        if ($valor === null) {
            return '—';
        }
        if ($campo == 'meio') {
            return PagamentoService::MEIOS[$valor] ?? (string) $valor;
        }
        if (is_bool($valor)) {
            return $valor ? 'sim' : 'não';
        }
        if (in_array($campo, ['principal', 'total'])) {
            return formataNumero($valor);
        }
        return (string) $valor;
    }

    // ---- negocio esquecido ----

    // 45 min, 3h10, 2 dias
    public static function duracao(float $minutos): string
    {
        $minutos = (int) floor($minutos);
        if ($minutos < 60) {
            return "{$minutos} min";
        }
        if ($minutos < 60 * 24) {
            return intdiv($minutos, 60) . 'h' . str_pad($minutos % 60, 2, '0', STR_PAD_LEFT);
        }
        $dias = intdiv($minutos, 60 * 24);
        return $dias == 1 ? '1 dia' : "{$dias} dias";
    }

    // Negocio aberto, com item, de PDV monitorado, parado ha' mais que o
    // tempo do PDV. Com $codportador (fechamento do caixa) pega todos os
    // abertos dos PDVs daquela gaveta, sem olhar o tempo.
    public static function esquecidos(?int $codportador = null): int
    {
        $sql = '
            select n.codnegocio, n.codpdv, p.codfilial, n.codusuario, n.valortotal, n.alteracao
            from tblnegocio n
            inner join tblpdv p on (p.codpdv = n.codpdv)
            where n.codnegociostatus = :aberto
            and p.monitoramento is not null
            and n.criacao >= p.monitoramento
            and exists (
                select 1 from tblnegocioprodutobarra i
                where i.codnegocio = n.codnegocio and i.inativo is null
            )
            and not exists (
                select 1 from tblocorrencia o
                where o.tipo = :tipo and o.tabela = \'tblnegocio\' and o.codigo = n.codnegocio
            )
        ';
        $params = [
            'aberto' => NegocioService::STATUS_ABERTO,
            'tipo' => static::TIPO_NEGOCIO_ESQUECIDO,
        ];
        if ($codportador) {
            $sql .= ' and p.codportador = :codportador';
            $params['codportador'] = $codportador;
        } else {
            $sql .= ' and n.alteracao < now() - make_interval(mins => p.minutosesquecido)';
        }

        $regs = DB::select($sql, $params);
        foreach ($regs as $reg) {
            $parado = static::duracao(Carbon::parse($reg->alteracao)->diffInMinutes(Carbon::now()));
            $descricao = $codportador
                ? "Aberto no fechamento do caixa (parado há {$parado})"
                : "Aberto há {$parado} sem alteração";
            $descricao .= ' — R$ ' . formataNumero($reg->valortotal);
            static::registrar([
                'tipo' => static::TIPO_NEGOCIO_ESQUECIDO,
                'tabela' => 'tblnegocio',
                'codigo' => $reg->codnegocio,
                'codnegocio' => $reg->codnegocio,
                'codpdv' => $reg->codpdv,
                'codfilial' => $reg->codfilial,
                'codusuario' => $reg->codusuario,
                'descricao' => $descricao,
                'valor' => (float) $reg->valortotal,
            ]);
        }
        return count($regs);
    }

    // ---- tela Ocorrencias do contas ----

    // Gerente ve as filiais dele; Financeiro e Administrador, todas.
    // Pendentes primeiro; ordem = 'valor' poe as que mais doem no topo.
    public static function listagem(array $filtros, int $page = 1, int $porPagina = 50): array
    {
        $where = ['true'];
        $params = [];

        $filiais = ConferenciaAutorizador::filiais();
        if ($filiais !== null) {
            if (empty($filiais)) {
                abort(403, 'Só Financeiro, Administrador ou Gerente!');
            }
            $where[] = 'o.codfilial in (' . implode(',', array_map('intval', $filiais)) . ')';
        }
        foreach (['codfilial', 'codpdv', 'tipo', 'codusuario', 'codnegocio'] as $campo) {
            if (!empty($filtros[$campo])) {
                $where[] = "o.{$campo} = :{$campo}";
                $params[$campo] = (int) $filtros[$campo];
            }
        }
        if (!empty($filtros['data_de'])) {
            $where[] = 'o.criacao >= :data_de';
            $params['data_de'] = Carbon::parse($filtros['data_de'])->startOfDay();
        }
        if (!empty($filtros['data_ate'])) {
            $where[] = 'o.criacao <= :data_ate';
            $params['data_ate'] = Carbon::parse($filtros['data_ate'])->endOfDay();
        }
        switch ($filtros['situacao'] ?? 'pendente') {
            case 'pendente':
                $where[] = 'o.conferencia is null';
                break;
            case 'conferida':
                $where[] = 'o.conferencia is not null';
                break;
        }
        $where = implode(' and ', $where);
        $ordem = ($filtros['ordem'] ?? null) == 'valor'
            ? 'o.valor desc, o.criacao desc'
            : '(o.conferencia is not null), o.criacao desc';

        $total = DB::selectOne("select count(*) as total from tblocorrencia o where {$where}", $params)->total;
        $regs = DB::select(static::sqlLinha() . " where {$where} order by {$ordem}, o.codocorrencia desc limit {$porPagina} offset " . (($page - 1) * $porPagina), $params);

        $auditorias = static::auditoriasDe(array_column($regs, 'codocorrencia'));
        return [
            'data' => array_map(fn ($r) => static::linha($r, $auditorias[$r->codocorrencia] ?? []), $regs),
            'meta' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $porPagina)),
                'per_page' => $porPagina,
                'total' => (int) $total,
            ],
        ];
    }

    public static function carregar(int $codocorrencia): array
    {
        $reg = DB::selectOne(static::sqlLinha() . ' where o.codocorrencia = :id', ['id' => $codocorrencia]);
        abort_if(!$reg, 404, 'Ocorrência não encontrada!');
        return static::linha($reg, static::auditoriasDe([$codocorrencia])[$codocorrencia] ?? []);
    }

    private static function sqlLinha(): string
    {
        return '
            select o.codocorrencia, o.tipo, o.tabela, o.codigo, o.codnegocio, o.codpdv, o.codfilial,
                o.codusuario, o.descricao, o.valor,
                o.conferencia, o.codusuarioconferencia, o.observacao, o.criacao,
                f.filial, p.apelido as pdv, u.usuario, uc.usuario as usuarioconferencia
            from tblocorrencia o
            inner join tblfilial f on (f.codfilial = o.codfilial)
            left join tblpdv p on (p.codpdv = o.codpdv)
            left join tblusuario u on (u.codusuario = o.codusuario)
            left join tblusuario uc on (uc.codusuario = o.codusuarioconferencia)
        ';
    }

    // as auditorias amarradas a cada ocorrencia: [codocorrencia => [...]]
    private static function auditoriasDe(array $codocorrencias): array
    {
        if (empty($codocorrencias)) {
            return [];
        }
        $regs = DB::select('
            select oa.codocorrencia, a.codauditoria, a.tabela, a.codigo, a.tipo, a.antes, a.depois,
                a.justificativa, a.criacao, u.usuario
            from tblocorrenciaauditoria oa
            inner join tblauditoria a on (a.codauditoria = oa.codauditoria)
            left join tblusuario u on (u.codusuario = a.codusuariocriacao)
            where oa.codocorrencia in (' . implode(',', array_map('intval', $codocorrencias)) . ')
            order by a.codauditoria
        ');
        $ret = [];
        foreach ($regs as $a) {
            $ret[$a->codocorrencia][] = [
                'codauditoria' => (int) $a->codauditoria,
                'tabela' => $a->tabela,
                'codigo' => (int) $a->codigo,
                'tipo' => (int) $a->tipo,
                'tipodescricao' => AuditoriaService::TIPOS[$a->tipo] ?? $a->tipo,
                'antes' => $a->antes ? json_decode($a->antes, true) : null,
                'depois' => $a->depois ? json_decode($a->depois, true) : null,
                'justificativa' => $a->justificativa,
                'usuario' => $a->usuario,
                'criacao' => Carbon::parse($a->criacao)->toIso8601String(),
            ];
        }
        return $ret;
    }

    private static function linha($r, array $auditorias): array
    {
        return [
            'codocorrencia' => (int) $r->codocorrencia,
            'tipo' => (int) $r->tipo,
            'tipodescricao' => static::TIPOS[$r->tipo] ?? $r->tipo,
            'tabela' => $r->tabela,
            'codigo' => $r->codigo ? (int) $r->codigo : null,
            'codnegocio' => $r->codnegocio ? (int) $r->codnegocio : null,
            'codpdv' => $r->codpdv ? (int) $r->codpdv : null,
            'pdv' => $r->pdv,
            'codfilial' => (int) $r->codfilial,
            'filial' => $r->filial,
            'codusuario' => $r->codusuario ? (int) $r->codusuario : null,
            'usuario' => $r->usuario,
            'descricao' => $r->descricao,
            'valor' => (float) $r->valor,
            // a do cancelamento, do estorno, da correcao
            'justificativa' => collect($auditorias)->pluck('justificativa')->filter()->unique()->implode(' · ') ?: null,
            'auditorias' => $auditorias,
            'conferencia' => $r->conferencia ? Carbon::parse($r->conferencia)->toIso8601String() : null,
            'codusuarioconferencia' => $r->codusuarioconferencia ? (int) $r->codusuarioconferencia : null,
            'usuarioconferencia' => $r->usuarioconferencia,
            'observacao' => $r->observacao,
            'criacao' => Carbon::parse($r->criacao)->toIso8601String(),
        ];
    }

    public static function conferir(Ocorrencia $oc, ?string $observacao): Ocorrencia
    {
        if (!empty($oc->conferencia)) {
            abort(422, 'Ocorrência já conferida!');
        }
        $oc->conferencia = Carbon::now();
        $oc->codusuarioconferencia = Auth::user()->codusuario;
        $observacao = trim($observacao ?? '');
        $oc->observacao = $observacao === '' ? null : mb_substr($observacao, 0, 500);
        $oc->save();
        return $oc;
    }

    public static function reabrir(Ocorrencia $oc): Ocorrencia
    {
        if (empty($oc->conferencia)) {
            abort(422, 'Ocorrência ainda não foi conferida!');
        }
        $oc->conferencia = null;
        $oc->codusuarioconferencia = null;
        $oc->observacao = null;
        $oc->save();
        return $oc;
    }
}
