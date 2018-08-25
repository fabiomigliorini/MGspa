<?php

namespace Mg\Pedido;

use Illuminate\Http\Request;
use DB;

use Mg\MgController;

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
        DB::beginTransaction();
        $model = PedidoRepository::insert($data);
        DB::commit();
        return response()->json($model, 201);
    }

    public function update (Request $request, $id)
    {
        $data = $request->all();
        $model = Pedido::findOrFail($id);
        DB::beginTransaction();
        $model = PedidoRepository::update($model, $data);
        DB::commit();
        return response()->json($model, 201);
    }

    public function destroy (Request $request, $id)
    {
        $model = Pedido::findOrFail($id);
        DB::beginTransaction();
        $model = PedidoRepository::delete($model);
        DB::commit();
        return response()->json($request->all(), 204);
    }

    // Itens
    public function indexItem (Request $request, $id)
    {
        $model = Pedido::findOrFail($id);
        $itens = $model->PedidoItemS()->paginate();
        return response()->json($itens, 206);
    }

    public function showItem (Request $request, $id, $iditem)
    {
        $model = PedidoItem::findOrFail($iditem);
        if ($model->codpedido != $id) {
            abort(404);
        }
        return response()->json($model, 200);
    }

    public function storeItem (Request $request, $id)
    {
        $data = $request->all();
        $data['codpedido'] = $id;
        DB::beginTransaction();
        $model = PedidoItemRepository::insert($data);
        DB::commit();
        return response()->json($model, 201);
    }

    public function updateItem (Request $request, $id, $iditem)
    {
        $data = $request->all();
        $model = PedidoItem::findOrFail($iditem);
        if ($model->codpedido != $id) {
            abort(404);
        }
        DB::beginTransaction();
        $model = PedidoItemRepository::update($model, $data);
        DB::commit();
        return response()->json($model, 201);
    }

    public function destroyItem (Request $request, $id, $iditem)
    {
        $model = PedidoItem::findOrFail($iditem);
        if ($model->codpedido != $id) {
            abort(404);
        }
        DB::beginTransaction();
        $model = PedidoItemRepository::delete($model);
        DB::commit();
        return response()->json($request->all(), 204);
    }

    public function produtosParaTransferir (Request $request, $codestoquelocalorigem, $codestoquelocaldestino)
    {
        $res = PedidoRepository::produtosParaTransferir($codestoquelocalorigem, $codestoquelocaldestino);
        return response()->json($res, 200);
    }

}
