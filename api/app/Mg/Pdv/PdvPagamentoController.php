<?php

namespace Mg\Pdv;

use Mg\Pagamento\PagamentoTituloListaResource;
use Mg\Pagamento\PagamentoTituloService;

/**
 * Recebimentos e pagamentos de titulos vistos do PDV (M6 doc-3; era a
 * listagem de liquidacoes). Os filtros sao os da listagem do contas mais o
 * PDV e a faixa de valor.
 */
class PdvPagamentoController
{
    public function index(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $filtros = $request->only(['codpagamento', 'codpessoa', 'codportador', 'meio', 'sentido', 'codusuariocriacao', 'cancelado', 'lancamento_de', 'lancamento_ate']);
        $filtros['cancelado'] = $filtros['cancelado'] ?? '';
        $qry = PagamentoTituloService::query()
            ->select('tblpagamento.*')
            ->with(['Pessoa:codpessoa,fantasia', 'PortadorDestino:codportador,portador,codfilial', 'PortadorOrigem:codportador,portador,codfilial', 'UsuarioCriacao:codusuario,usuario', 'Pdv:codpdv,apelido']);
        PagamentoTituloService::filtrar($qry, $filtros);
        if (!empty($request->codpdv)) {
            $qry->where('tblpagamento.codpdv', $request->codpdv);
        }
        if ($request->valor_de > 0) {
            $qry->where('tblpagamento.total', '>=', $request->valor_de);
        }
        if ($request->valor_ate > 0) {
            $qry->where('tblpagamento.total', '<=', $request->valor_ate);
        }
        $qry->orderBy('tblpagamento.lancamento', 'desc')
            ->orderBy('tblpagamento.criacao', 'desc')
            ->orderBy('tblpagamento.codpagamento', 'desc');
        return PagamentoTituloListaResource::collection($qry->paginate(100));
    }
}
