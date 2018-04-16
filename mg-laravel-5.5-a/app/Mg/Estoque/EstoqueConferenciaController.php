<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Marca\MarcaRepository;

class EstoqueConferenciaController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $res = MarcaRepository::buscaProdutosParaConferencia(
            $request->codmarca,
            $request->codestoquelocal,
            $request->fiscal
        );

        return response()->json($res, 206);
    }

}
