<?php

namespace Estoque;

use Illuminate\Http\Request;
use App\Mg\MgController;

class EstoqueEstatisticaController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $res = EstoqueEstatisticaRepository::buscaEstatisticaProduto($id, null, $request->codprodutovariacao, $request->codestoquelocal);

        return response()->json($res, 206);
    }

}
