<?php

namespace Mg\Woo;

use App\Http\Controllers\Controller;

class WooPedidoController extends Controller
{
    public function index(WooPedidoRequest $request)
    {
        // inicializa query
        $qry = WooPedido::query();

        // filtra id
        if (!empty($request->id)) {
            $qry->where('id', $request->id);
        }

        // filtra status
        if (!empty($request->status)) {
            $status = $request->status;
            if (!is_array($status)) {
                $status = [$status];
            }
            $qry->whereIn('status', $status);
        }

        // filtra nome
        if (!empty($request->nome)) {
            $palavras = $request->nome;
            foreach (explode(' ', $palavras) as $palavra) {
                $qry->where('nome', 'ilike', '%' . $palavra . '%');
            }
        }

        // filtra data
        if (!empty($request->criacaowoo_de)) {
            $qry->where('criacaowoo', '>=', "{$request->criacaowoo_de} 00:00:00");
        }
        if (!empty($request->criacaowoo_ate)) {
            $qry->where('criacaowoo', '<=', "{$request->criacaowoo_ate} 23:59:59");
        }

        // valor total
        if (!empty($request->valortotal_de)) {
            $qry->where('valortotal', '>=', $request->valortotal_de);
        }
        if (!empty($request->valortotal_ate)) {
            $qry->where('valortotal', '<=', $request->valortotal_ate);
        }

        // pagina
        $peds = $qry->paginate(50);

        // retorna formatado
        return WooPedidoResource::collection($peds);
    }

    public function painel(WooPedidoRequest $request)
    {
        // pedidos "em aberto"
        $peds = WooPedido::whereIn('status', [
            'pending',
            'processing',
            'on-hold'
        ])->get();

        // 10 ultimos pedidos "finalizados" de cada status
        foreach (['completed', 'cancelled', 'refunded', 'failed'] as $status) {
            $part = WooPedido::where('status', $status)->orderBy('alteracaowoo', 'desc')->limit(10)->get();
            $peds = $peds->merge($part);
        }

        // retorna formatado
        return WooPedidoResource::collection($peds);
    }

    public function reprocessar($id)
    {
        // valida id
        $id = (int) $id;
        if (!$id) {
            return response()->json(['message' => 'ID inválido'], 400);
        }

        // busca pedido no woo  
        $wps = new WooPedidoService();
        $wps->buscarPedido($id);

        // verifica se foi importado 
        $pedido = WooPedido::where('id', (int) $id)->first();
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }

        $wps->importarNegocio($pedido, true);

        // retorna formatado
        return new WooPedidoResource($pedido);
    }

    public function buscarNovos()
    {
        // busca novos pedidos no woo pelo status
        $wps = new WooPedidoService();
        $peds = $wps->buscarNovos();

        // retorna formatado
        return WooPedidoResource::collection($peds);
    }

    public function buscarPorAlteracao()
    {
        // busca novos pedidos no woo pela data de alteracao
        $wps = new WooPedidoService();
        $peds = $wps->buscarPorAlteracao();

        // retorna formatado
        return WooPedidoResource::collection($peds);
    }

    //alterar status do pedido
    public function alteraStatus(WooPedidoStatusRequest $request, $id)
    {
        // valida id
        $id = (int) $id;
        if (!$id) {
            return response()->json(['message' => 'ID inválido'], 400);
        }

        // acha pedido
        $pedido = WooPedido::where('id', (int) $id)->first();
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }

        // altera status
        $wps = new WooPedidoService();
        $pedido = $wps->alterarStatus($pedido, $request->status);

        // retorna pedido com status alterado
        return new WooPedidoResource($pedido);
    }
}
