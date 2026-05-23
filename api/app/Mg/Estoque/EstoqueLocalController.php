<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;

class EstoqueLocalController extends MgController
{
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $qry = EstoqueLocalService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        return response()->json($res, 206);
    }

    public function show(Request $request, $id)
    {
        $model = EstoqueLocal::findOrFail($id);
        return response()->json($model, 200);
    }

    public function autocompletar(Request $request)
    {
        $res = EstoqueLocalService::autocompletar($request->all());
        return response()->json($res, 206);
    }
}
