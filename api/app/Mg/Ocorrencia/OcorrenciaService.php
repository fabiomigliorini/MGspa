<?php

namespace Mg\Ocorrencia;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Negocio\NegocioService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Pdv\Pdv;

/**
 * Livro de ocorrencias (TASK-205): o que o gerente confere no fim do dia.
 *
 * Tipos < 10 nascem no PDV (com o motivo dado pelo caixa, mesmo offline) e
 * chegam na sincronizacao; tipos >= 10 nascem no servidor, uma vez so' por
 * registro (uk_tblocorrencia_servidor). Tudo so' vale para negocio de PDV
 * monitorado (tblpdv.monitoramento) criado a partir da data.
 */
class OcorrenciaService
{
    // nascem no PDV
    const TIPO_ITEM_EXCLUIDO = 1;
    const TIPO_QUANTIDADE_DIMINUIDA = 2;
    const TIPO_PRECO_DIMINUIDO = 3;
    const TIPO_PAGAMENTO_EXCLUIDO = 4;

    // nascem no servidor
    const TIPO_NEGOCIO_CANCELADO = 10;
    const TIPO_PAGAMENTO_ESTORNADO = 11;
    const TIPO_VALE_ESTORNADO = 12;
    const TIPO_DESCONTO_ACIMA = 13;
    const TIPO_NEGOCIO_ESQUECIDO = 14;

    const TIPOS = [
        self::TIPO_ITEM_EXCLUIDO => 'Item excluído',
        self::TIPO_QUANTIDADE_DIMINUIDA => 'Quantidade diminuída',
        self::TIPO_PRECO_DIMINUIDO => 'Preço diminuído',
        self::TIPO_PAGAMENTO_EXCLUIDO => 'Pagamento excluído',
        self::TIPO_NEGOCIO_CANCELADO => 'Negócio cancelado',
        self::TIPO_PAGAMENTO_ESTORNADO => 'Pagamento estornado',
        self::TIPO_VALE_ESTORNADO => 'Vale estornado',
        self::TIPO_DESCONTO_ACIMA => 'Desconto acima do permitido',
        self::TIPO_NEGOCIO_ESQUECIDO => 'Negócio esquecido',
    ];

    const TIPOS_PDV = [
        self::TIPO_ITEM_EXCLUIDO,
        self::TIPO_QUANTIDADE_DIMINUIDA,
        self::TIPO_PRECO_DIMINUIDO,
        self::TIPO_PAGAMENTO_EXCLUIDO,
    ];

    const MOTIVO_OUTRO = 9;

    const MOTIVOS = [
        1 => 'Bipou errado',
        2 => 'Cliente desistiu',
        3 => 'Preço diferente da gôndola',
        4 => 'Produto com defeito',
        5 => 'Valor digitado errado',
        6 => 'Cliente trocou a forma de pagamento',
        self::MOTIVO_OUTRO => 'Outro',
    ];

    const MOTIVOS_ITEM = [1, 2, 3, 4, self::MOTIVO_OUTRO];
    const MOTIVOS_PAGAMENTO = [5, 6, self::MOTIVO_OUTRO];

    // desconto a vista sem autorizacao no cadastro (TASK-190 troca pela
    // regra por forma de pagamento e categoria de cliente)
    const DESCONTO_AVISTA = 5;
    const MEIOS_AVISTA = [
        PagamentoService::MEIO_DINHEIRO,
        PagamentoService::MEIO_DEBITO,
        PagamentoService::MEIO_PIX,
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
    private static function filial(?Pdv $pdv, $codfilial): ?int
    {
        return $pdv->codfilial ?? $codfilial;
    }

    // Evento do servidor sai uma vez so' por registro
    public static function registrar(array $dados): Ocorrencia
    {
        if ($dados['tipo'] >= 10) {
            return Ocorrencia::firstOrCreate([
                'tipo' => $dados['tipo'],
                'tabela' => $dados['tabela'],
                'codigo' => $dados['codigo'],
            ], $dados);
        }
        return Ocorrencia::create($dados);
    }

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
            'antes' => $foto,
            'justificativa' => mb_substr($justificativa, 0, 300),
        ]);
    }

    // Estorno de recebimento (titulo, vale colaborador, adiantamento). O
    // pagamento de venda e' cancelado pelo negocio e nao passa por aqui.
    public static function pagamentoEstornado(Pagamento $pag, bool $vale, string $justificativa): ?Ocorrencia
    {
        if (!static::monitorado($pag->Pdv, $pag->criacao)) {
            return null;
        }
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
            'codpdv' => $pag->codpdv,
            'codfilial' => static::filial($pag->Pdv, $pag->codfilial),
            'codusuario' => Auth::user()->codusuario ?? null,
            'descricao' => mb_substr($descricao, 0, 300),
            'valor' => abs((float) $pag->total),
            'antes' => [
                'meio' => $pag->meio,
                'total' => (float) $pag->total,
                'estado' => PagamentoService::ESTADO_EFETIVADO,
            ],
            'justificativa' => mb_substr($justificativa, 0, 300),
        ]);
    }

    // No fechamento: desconto acima do maior entre o do cadastro do cliente
    // e 5% sobre a parte paga a vista (pix, dinheiro, debito)
    public static function descontoNoFechamento(Negocio $negocio): ?Ocorrencia
    {
        if (!$negocio->NaturezaOperacao->venda || !$negocio->NaturezaOperacao->financeiro) {
            return null;
        }
        if (!static::monitorado($negocio->Pdv, $negocio->criacao)) {
            return null;
        }
        $desconto = round((float) $negocio->valordesconto, 2);
        if ($desconto <= 0) {
            return null;
        }
        $percentualPessoa = (float) ($negocio->Pessoa->desconto ?? 0);
        $permitidoPessoa = round((float) $negocio->valorprodutos * $percentualPessoa / 100, 2);
        // principal = valor antes do desconto do pagamento, sem troco
        $baseAvista = (float) $negocio->PagamentoS()
            ->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)
            ->whereIn('meio', static::MEIOS_AVISTA)
            ->sum('principal');
        $permitidoAvista = round($baseAvista * static::DESCONTO_AVISTA / 100, 2);
        $permitido = max($permitidoPessoa, $permitidoAvista);
        $excesso = round($desconto - $permitido, 2);
        if ($excesso <= 0.01) {
            return null;
        }
        $percentual = $negocio->valorprodutos > 0 ? $desconto / $negocio->valorprodutos * 100 : 0;
        $descricao = "Desconto de R$ " . formataNumero($desconto) . " (" . formataNumero($percentual, 1) . "%)"
            . ", permitido R$ " . formataNumero($permitido);
        return static::registrar([
            'tipo' => static::TIPO_DESCONTO_ACIMA,
            'tabela' => 'tblnegocio',
            'codigo' => $negocio->codnegocio,
            'codnegocio' => $negocio->codnegocio,
            'codpdv' => $negocio->codpdv,
            'codfilial' => static::filial($negocio->Pdv, $negocio->codfilial),
            'codusuario' => $negocio->codusuario,
            'descricao' => $descricao,
            'valor' => $excesso,
            'depois' => [
                'valordesconto' => $desconto,
                'valorprodutos' => (float) $negocio->valorprodutos,
                'permitido' => $permitido,
                'percentualpessoa' => $percentualPessoa,
                'baseavista' => round($baseAvista, 2),
                'percentualavista' => static::DESCONTO_AVISTA,
            ],
        ]);
    }

    // Ocorrencias que o PDV registrou (com o motivo do caixa). So' insere:
    // a que ja' chegou (mesmo uuid) e' ignorada.
    public static function importarDoPdv(Negocio $negocio, array $ocorrencias): void
    {
        foreach ($ocorrencias as $dados) {
            if (!is_array($dados) || empty($dados['uuid']) || Ocorrencia::where('uuid', $dados['uuid'])->exists()) {
                continue;
            }
            $tipo = (int) ($dados['tipo'] ?? 0);
            if (!in_array($tipo, static::TIPOS_PDV)) {
                abort(422, "Tipo de ocorrência inválido ({$tipo})!");
            }
            $motivo = (int) ($dados['motivo'] ?? 0);
            $motivos = $tipo == static::TIPO_PAGAMENTO_EXCLUIDO ? static::MOTIVOS_PAGAMENTO : static::MOTIVOS_ITEM;
            if (!in_array($motivo, $motivos)) {
                abort(422, "Motivo da ocorrência inválido ({$motivo})!");
            }
            $justificativa = trim($dados['justificativa'] ?? '');
            if ($motivo == static::MOTIVO_OUTRO && $justificativa === '') {
                abort(422, 'Informe a justificativa quando o motivo for Outro!');
            }

            $tabela = 'tblnegocio';
            $codigo = $negocio->codnegocio;
            if ($tipo != static::TIPO_PAGAMENTO_EXCLUIDO && !empty($dados['uuidregistro'])) {
                $npb = NegocioProdutoBarra::where('uuid', $dados['uuidregistro'])
                    ->where('codnegocio', $negocio->codnegocio)
                    ->first();
                if ($npb) {
                    $tabela = 'tblnegocioprodutobarra';
                    $codigo = $npb->codnegocioprodutobarra;
                }
            }

            static::registrar([
                'uuid' => $dados['uuid'],
                'tipo' => $tipo,
                'tabela' => $tabela,
                'codigo' => $codigo,
                'codnegocio' => $negocio->codnegocio,
                'codpdv' => $negocio->codpdv,
                'codfilial' => static::filial($negocio->Pdv, $negocio->codfilial),
                'codusuario' => Auth::user()->codusuario ?? null,
                'descricao' => mb_substr($dados['descricao'] ?? static::TIPOS[$tipo], 0, 300),
                'valor' => round(abs((float) ($dados['valor'] ?? 0)), 2),
                'antes' => $dados['antes'] ?? null,
                'depois' => $dados['depois'] ?? null,
                'motivo' => $motivo,
                'justificativa' => $justificativa === '' ? null : mb_substr($justificativa, 0, 300),
                'criacao' => static::momento($dados['criacao'] ?? null),
            ]);
        }
    }

    // Momento informado pelo PDV; invalido ou no futuro (relogio adiantado)
    // vira agora, sem derrubar a sincronizacao
    private static function momento($valor): Carbon
    {
        try {
            $momento = empty($valor) ? null : Carbon::parse($valor);
        } catch (\Throwable $th) {
            $momento = null;
        }
        if (!$momento || $momento->isFuture()) {
            return Carbon::now();
        }
        return $momento;
    }

    // As do PDV, no formato do PDV (o documento do Dexie e' substituido pela
    // resposta do servidor e nao pode perde-las)
    public static function paraPdv(Negocio $negocio): array
    {
        return Ocorrencia::where('codnegocio', $negocio->codnegocio)
            ->whereIn('tipo', static::TIPOS_PDV)
            ->orderBy('criacao')
            ->orderBy('codocorrencia')
            ->get()
            ->map(fn ($oc) => [
                'uuid' => $oc->uuid,
                'tipo' => $oc->tipo,
                'descricao' => $oc->descricao,
                'valor' => $oc->valor,
                'antes' => $oc->antes,
                'depois' => $oc->depois,
                'motivo' => $oc->motivo,
                'justificativa' => $oc->justificativa,
                'criacao' => $oc->criacao?->toIso8601String(),
            ])
            ->all();
    }

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
                'antes' => ['alteracao' => Carbon::parse($reg->alteracao)->toIso8601String()],
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

        return [
            'data' => array_map(fn ($r) => static::linha($r), $regs),
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
        return static::linha($reg);
    }

    private static function sqlLinha(): string
    {
        return '
            select o.codocorrencia, o.tipo, o.tabela, o.codigo, o.codnegocio, o.codpdv, o.codfilial,
                o.codusuario, o.descricao, o.valor, o.antes, o.depois, o.motivo, o.justificativa,
                o.conferencia, o.codusuarioconferencia, o.observacao, o.criacao,
                f.filial, p.apelido as pdv, u.usuario, uc.usuario as usuarioconferencia
            from tblocorrencia o
            inner join tblfilial f on (f.codfilial = o.codfilial)
            left join tblpdv p on (p.codpdv = o.codpdv)
            left join tblusuario u on (u.codusuario = o.codusuario)
            left join tblusuario uc on (uc.codusuario = o.codusuarioconferencia)
        ';
    }

    private static function linha($r): array
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
            'antes' => $r->antes ? json_decode($r->antes, true) : null,
            'depois' => $r->depois ? json_decode($r->depois, true) : null,
            'motivo' => $r->motivo ? (int) $r->motivo : null,
            'motivodescricao' => $r->motivo ? (static::MOTIVOS[$r->motivo] ?? $r->motivo) : null,
            'justificativa' => $r->justificativa,
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
