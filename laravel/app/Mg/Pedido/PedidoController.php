<?php

namespace Mg\Pedido;

use Illuminate\Http\Request;
use DB;

use Mg\MgController;

class PedidoController extends MgController
{

    public function index (Request $request)
    {
        $filter = $request->all();

        $sql = '
          SELECT
            p.codpedido
            , p.indtipo
            , p.indstatus
            , p.codestoquelocal
            , el.estoquelocal
            , p.codestoquelocalorigem
            , el_o.estoquelocal as estoquelocalorigem
            , p.observacoes
            , p.codgrupoeconomico
            , ge.grupoeconomico
            , p.criacao
            , p.codusuariocriacao
            , u_c.usuario as usuariocriacao
            , p.alteracao
            , p.codusuarioalteracao
            , (SELECT COUNT(pi.codpedidoitem) FROM tblpedidoitem pi WHERE pi.codpedido = p.codpedido) as itens
          FROM tblpedido p
          LEFT JOIN tblestoquelocal el ON (el.codestoquelocal = p.codestoquelocal)
          LEFT JOIN tblestoquelocal el_o ON (el_o.codestoquelocal = p.codestoquelocalorigem)
          LEFT JOIN tblgrupoeconomico ge ON (ge.codgrupoeconomico = p.codgrupoeconomico)
          LEFT JOIN tblusuario u_c ON (u_c.codusuario = p.codusuariocriacao)
        ';

        $params = [];
        if (!empty($filter['indstatus'])) {
          $sql .= (empty($params)?'WHERE':'AND') . ' p.indstatus = :indstatus ';
          $params['indstatus'] = $filter['indstatus'];
        }

        if (!empty($filter['indtipo'])) {
          $sql .= (empty($params)?'WHERE':'AND') . ' p.indtipo IN (:indtipo) ';
          $params['indtipo'] = $filter['indtipo'];
        }

        if (!empty($filter['codestoquelocal'])) {
          $sql .= (empty($params)?'WHERE':'AND') . ' p.codestoquelocal = :codestoquelocal ';
          $params['codestoquelocal'] = $filter['codestoquelocal'];
        }

        if (!empty($filter['codestoquelocalorigem'])) {
          $sql .= (empty($params)?'WHERE':'AND') . ' p.codestoquelocalorigem = :codestoquelocalorigem ';
          $params['codestoquelocalorigem'] = $filter['codestoquelocalorigem'];
        }

        if (!empty($filter['codgrupoeconomico'])) {
          $sql .= (empty($params)?'WHERE':'AND') . ' p.codgrupoeconomico = :codgrupoeconomico ';
          $params['codgrupoeconomico'] = $filter['codgrupoeconomico'];
        }

        switch ($filter['indstatus']??null) {
          case Pedido::STATUS_PENDENTE:
            $sql .= ' ORDER BY p.criacao ASC, p.codpedido ASC ';
            break;

          default:
            $sql .= ' ORDER BY p.alteracao DESC, p.codpedido DESC ';
            break;
        }

        $params['limit'] = 50;
        $params['offset'] = (($filter['page']??1)-1) * $params['limit'];
        $sql .= '
          LIMIT :limit OFFSET :offset
        ';

        $res = DB::select($sql, $params);

        $tipos = Pedido::TIPO;
        $status = Pedido::STATUS;
        foreach ($res as $pedido) {
          $pedido->tipo = $tipos[$pedido->indtipo];
          $pedido->status = $status[$pedido->indstatus];
        }

        return response()->json($res, 206);
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
