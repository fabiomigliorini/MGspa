<?php

namespace Mg\Titulo;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Pagamento\PagamentoTituloAutorizador;
use Mg\Usuario\Autorizador;

/**
 * Vale colaborador e adiantamentos lancados no contas (M8 doc-3): a mesma
 * tela e o mesmo servico do PDV, com as formas do financeiro. Rota
 * POST v1/titulo/adiantamento; quem pode e' quem baixa titulo no contas.
 */
class TituloAdiantamentoController
{
    public function store(TituloAdiantamentoStoreRequest $request)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Cobranca', 'Gerente', 'Caixa']);
        $dados = $request->validated();
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioAdiantamento(Auth::user()->codusuario, $dados);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        DB::beginTransaction();
        $pags = TituloAdiantamentoService::lancar($dados);
        DB::commit();
        return PagamentoDetalheResource::collection($pags);
    }
}
