<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;

class EstoqueSaldoGridController extends MgController
{
    public function index(Request $request)
    {
        $agrupamento = $request->get('agrupamento', 'secaoproduto');
        $valor = $request->get('valor', 'custo');
        $filtro = $request->except(['agrupamento', 'valor', 'page']);

        $itens = EstoqueSaldoGridService::totais($agrupamento, $valor, $filtro);

        return response()->json([
            'agrupamento' => $agrupamento,
            'valor' => $valor,
            'itens' => $itens,
        ], 200);
    }
}
