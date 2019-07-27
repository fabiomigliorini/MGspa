<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Estoque\EstoqueLocalRepository;

class EstoqueLocalController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $qry = EstoqueLocalRepository::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        return response()->json($res, 206);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $model = EstoqueLocal::findOrFail($id, $request->get('fields'));
        return response()->json($model, 200);
    }

    public function autocompletar (Request $request) {
        $res = EstoqueLocalRepository::autocompletar($request->all());
        return response()->json($res, 206);
    }


}
