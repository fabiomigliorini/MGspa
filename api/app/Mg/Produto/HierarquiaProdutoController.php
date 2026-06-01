<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Mg\MgController;

class HierarquiaProdutoController extends MgController
{
    public function filhos(Request $request)
    {
        $nivel = $request->get('nivel', 'raiz');
        $id = $request->get('id');
        $res = HierarquiaProdutoService::filhos($nivel, $id ? (int) $id : null);
        return response()->json($res, 200);
    }
}
