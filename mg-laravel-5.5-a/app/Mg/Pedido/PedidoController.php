<?php

namespace Mg\Pedido;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PedidoController extends MgController
{

    public function index (Request $request)
    {
        $model = Pedido::all();
        return response()->json($model, 206);
    }

    public function show (Request $request, $id)
    {
        $model = Pedido::findOrFail($id);
        return response()->json($model, 200);
    }

    public function store (Request $request)
    {
        $data = $request->all();
        $model = PedidoRepository::insert($data);
        return response()->json($model, 201);
    }

    public function update (Request $request, $id)
    {
        $data = $request->all();
        $model = Pedido::findOrFail($id);
        $model = PedidoRepository::update($model, $data);
        return response()->json($model, 201);
    }

    public function destroy (Request $request, $id)
    {
        $model = Pedido::findOrFail($id);
        $model = PedidoRepository::delete($model);
        return response()->json($request->all(), 204);
    }

}
