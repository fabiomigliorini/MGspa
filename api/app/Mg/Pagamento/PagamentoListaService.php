<?php

namespace Mg\Pagamento;

use Carbon\Carbon;

/**
 * Listagem unica de pagamentos (M6.1 doc-3): venda, titulo e avulso (taxa,
 * tarifa, rendimento), em qualquer estado. Ajuste, transferencia e item do
 * caixa nao sao pagamento (movimento do portador). A mesma consulta serve o contas (v1/pagamento,
 * filiais do usuario) e o PDV (v1/pdv/pagamento, travada no PDV).
 */
class PagamentoListaService
{
    // origem do pagamento: o documento que ele quitou
    const ORIGEM_VENDA = 'V';
    const ORIGEM_TITULO = 'T';
    const ORIGEM_AVULSO = 'A';

    const ORIGENS = [
        self::ORIGEM_VENDA => 'Venda',
        self::ORIGEM_TITULO => 'Títulos',
        self::ORIGEM_AVULSO => 'Avulso',
    ];

    const FILTROS = [
        'codpagamento',
        'transacao_de',
        'transacao_ate',
        'codfilial',
        'codpdv',
        'codportador',
        'meio',
        'estado',
        'origem',
        'codpessoa',
        'codmaquineta',
        'documento',
        'codusuariocriacao',
    ];

    const RELACOES = [
        'Pessoa:codpessoa,fantasia',
        'Negocio:codnegocio,codpessoa,lancamento',
        'Negocio.Pessoa:codpessoa,fantasia',
        'PortadorDestino:codportador,portador,codfilial,tipo',
        'PortadorOrigem:codportador,portador,codfilial,tipo',
        'Maquineta:codmaquineta,apelido',
        'Pdv:codpdv,apelido',
        'UsuarioCriacao:codusuario,usuario',
        'MovimentoTituloS:codmovimentotitulo,codpagamento,codtitulo,codtipomovimentotitulo,codmovimentotituloestorno',
        'MovimentoTituloS.Titulo:codtitulo,numero',
    ];

    // Venda, titulo ou avulso (o resto)
    public static function origem(Pagamento $pag): string
    {
        if (!empty($pag->codnegocio)) {
            return static::ORIGEM_VENDA;
        }
        if ($pag->MovimentoTituloS->isNotEmpty() || !empty($pag->codperiodocolaboradoracerto)) {
            return static::ORIGEM_TITULO;
        }
        return static::ORIGEM_AVULSO;
    }

    // CR entrou dinheiro, DB saiu, TR transferencia, CP encontro de contas
    public static function operacao(Pagamento $pag): string
    {
        if (!empty($pag->codportadordestino) && !empty($pag->codportadororigem)) {
            return 'TR';
        }
        if (!empty($pag->codportadordestino)) {
            return 'CR';
        }
        if (!empty($pag->codportadororigem)) {
            return 'DB';
        }
        if (!empty($pag->codnegocio)) {
            return empty($pag->codpagamentoorigem) ? 'CR' : 'DB';
        }
        $valor = PagamentoTituloService::valor($pag);
        if ($valor < 0) {
            return 'CR';
        }
        return ($valor > 0) ? 'DB' : 'CP';
    }

    // Valor com o sinal da liquidacao antiga (relatorio): negativo = entrou,
    // positivo = saiu; titulo pelo movimento, o resto pela operacao
    public static function valorComSinal(Pagamento $pag): float
    {
        if ($pag->MovimentoTituloS->isNotEmpty() && empty($pag->codnegocio)) {
            return PagamentoTituloService::valor($pag);
        }
        switch (static::operacao($pag)) {
            case 'CR':
                return -(float) $pag->total;
            case 'DB':
                return (float) $pag->total;
        }
        return 0;
    }

    // Pessoa que aparece: a da venda, ou a do pagamento
    public static function pessoa(Pagamento $pag)
    {
        return optional($pag->Negocio)->Pessoa ?? $pag->Pessoa;
    }

    public static function filtrar($q, array $filtros)
    {
        if (array_key_exists('filiais_permitidas', $filtros) && $filtros['filiais_permitidas'] !== null) {
            $filiais = $filtros['filiais_permitidas'];
            if (empty($filiais)) {
                $q->whereRaw('1 = 0');
            } else {
                $q->whereIn('tblpagamento.codfilial', $filiais);
            }
        }
        if (!empty($filtros['codpagamento'])) {
            $cod = preg_replace('/[^0-9]/', '', (string) $filtros['codpagamento']);
            $q->where(function ($w) use ($cod) {
                $w->where('tblpagamento.codpagamento', $cod)
                    ->orWhere('tblpagamento.codliquidacaotituloantigo', $cod);
            });
        }
        // o indice (estado, transacao) atende o periodo: sem filtro de
        // estado, todos os tres
        $estados = array_values(array_filter((array) ($filtros['estado'] ?? [])));
        $q->whereIn('tblpagamento.estado', empty($estados) ? array_keys(PagamentoService::ESTADOS) : $estados);
        foreach ([
            'transacao_de' => ['>=', 'startOfDay'],
            'transacao_ate' => ['<=', 'endOfDay'],
        ] as $key => [$op, $bound]) {
            if (!empty($filtros[$key])) {
                $q->where('tblpagamento.transacao', $op, Carbon::parse($filtros[$key])->{$bound}()->format('Y-m-d H:i:s'));
            }
        }
        foreach (['codfilial', 'codpdv', 'codmaquineta', 'codusuariocriacao'] as $col) {
            if (!empty($filtros[$col])) {
                $q->where("tblpagamento.{$col}", $filtros[$col]);
            }
        }
        if (!empty($filtros['codportador'])) {
            $q->where(function ($w) use ($filtros) {
                $w->where('tblpagamento.codportadordestino', $filtros['codportador'])
                    ->orWhere('tblpagamento.codportadororigem', $filtros['codportador']);
            });
        }
        if (!empty($filtros['meio'])) {
            $q->whereIn('tblpagamento.meio', (array) $filtros['meio']);
        }
        if (!empty($filtros['codpessoa'])) {
            $cod = (int) $filtros['codpessoa'];
            $q->where(function ($w) use ($cod) {
                $w->where('tblpagamento.codpessoa', $cod)
                    ->orWhereExists(fn($e) => $e->selectRaw('1')->from('tblnegocio as n')
                        ->whereColumn('n.codnegocio', 'tblpagamento.codnegocio')
                        ->where('n.codpessoa', $cod));
            });
        }
        if (!empty($filtros['origem'])) {
            $origens = (array) $filtros['origem'];
            $q->where(function ($w) use ($origens) {
                $temTitulo = fn($e) => $e->selectRaw('1')->from('tblmovimentotitulo as mt')
                    ->whereColumn('mt.codpagamento', 'tblpagamento.codpagamento');
                foreach ($origens as $origem) {
                    switch ($origem) {
                        case static::ORIGEM_VENDA:
                            $w->orWhereNotNull('tblpagamento.codnegocio');
                            break;
                        case static::ORIGEM_TITULO:
                            $w->orWhere(fn($t) => $t->whereNull('tblpagamento.codnegocio')
                                ->where(fn($x) => $x->whereExists($temTitulo)
                                    ->orWhereNotNull('tblpagamento.codperiodocolaboradoracerto')));
                            break;
                        case static::ORIGEM_AVULSO:
                            $w->orWhere(fn($t) => $t->whereNull('tblpagamento.codnegocio')
                                ->whereNull('tblpagamento.codperiodocolaboradoracerto')
                                ->whereNotExists($temTitulo));
                            break;
                    }
                }
            });
        }
        // numero do documento: o da venda ou o de um titulo baixado
        if (!empty($filtros['documento'])) {
            $doc = trim((string) $filtros['documento']);
            $codnegocio = ltrim($doc, '#');
            $q->where(function ($w) use ($doc, $codnegocio) {
                if (ctype_digit($codnegocio)) {
                    $w->orWhere('tblpagamento.codnegocio', (int) $codnegocio);
                }
                $w->orWhereExists(fn($e) => $e->selectRaw('1')->from('tblmovimentotitulo as mt')
                    ->join('tbltitulo as t', 't.codtitulo', '=', 'mt.codtitulo')
                    ->whereColumn('mt.codpagamento', 'tblpagamento.codpagamento')
                    ->where('t.numero', 'ilike', $doc . '%'));
            });
        }
        return $q;
    }

    public static function listar(array $filtros, int $porPagina = 50)
    {
        $q = Pagamento::query()
            ->select('tblpagamento.*')
            ->with(static::RELACOES);
        static::filtrar($q, $filtros);
        $q->orderBy('tblpagamento.transacao', 'desc')
            ->orderBy('tblpagamento.codpagamento', 'desc');
        return $q->paginate($porPagina);
    }

    public static function carregar(int $id): Pagamento
    {
        return Pagamento::with([
            'Pessoa',
            'Negocio:codnegocio,codpessoa,codfilial,codpdv,lancamento,valortotal,codnegociostatus',
            'Negocio.Pessoa:codpessoa,fantasia',
            'PortadorDestino:codportador,portador,codfilial,tipo',
            'PortadorOrigem:codportador,portador,codfilial,tipo',
            'Maquineta:codmaquineta,apelido,serial',
            'Pdv:codpdv,apelido',
            'Filial:codfilial,filial',
            'PagamentoOrigem:codpagamento,meio,total,transacao,codnegocio',
            'PagamentoContrarioS:codpagamento,codpagamentoorigem,meio,estado,total,transacao',
            'UsuarioCriacao:codusuario,usuario',
            'UsuarioAlteracao:codusuario,usuario',
            'MovimentoTituloS' => function ($q) {
                $q->orderBy('codmovimentotitulo')
                    ->with([
                        'Titulo:codtitulo,codpessoa,codfilial,numero,vencimento,fatura,nossonumero,boleto,gerencial,codportador,codtituloagrupamento,valor,saldo',
                        'Titulo.Pessoa:codpessoa,fantasia',
                        'Titulo.Filial:codfilial,filial',
                        'Titulo.Portador:codportador,portador',
                        'TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo',
                    ]);
            },
        ])->findOrFail($id);
    }
}
