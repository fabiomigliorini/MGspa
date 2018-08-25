<?php

namespace Mg\Pedido;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PedidoController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    // public function index(Request $request)
    // {
    //     list($filter, $sort, $fields) = $this->filtros($request);
    //     $qry = PessoaRepository::pesquisar($filter, $sort, $fields);
    //     $res = $qry->paginate()->appends($request->all());
    //     return response()->json($res, 206);
    // }

}
