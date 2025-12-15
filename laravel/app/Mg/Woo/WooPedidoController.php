<?php

namespace Mg\Woo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WooPedidoController extends Controller
{
    public function index(WooPedidoRequest $request)
    {
        // inicializa query
        $qry = WooPedido::query();

        // filtra id
        if ($request->has('id')) {
            $qry->where('id', $request->id);
        }

        // filtra status
        if ($request->has('status')) {
            $status = $request->status;
            if (!is_array($status)) {
                $status = [$status];
            }
            $qry->whereIn('status', $status);
        }

        // filtra nome
        if ($request->has('nome')) {
            $palavras = $request->nome;
            foreach (explode(' ', $palavras) as $palavra) {
                $qry->where('nome', 'ilike', '%' . $palavra . '%');
            }
        }

        // filtra data
        if ($request->has('criacaowoo_de')) {
            $qry->where('criacaowoo', '>=', "{$request->criacaowoo_de} 00:00:00");
        }
        if ($request->has('criacaowoo_ate')) {
            $qry->where('criacaowoo', '<=', "{$request->criacaowoo_ate} 23:59:59");
        }

        // valor total
        if ($request->has('valortotal_de')) {
            $qry->where('valortotal', '>=', $request->valortotal_de);
        }
        if ($request->has('valortotal_ate')) {
            $qry->where('valortotal', '<=', $request->valortotal_ate);
        }

        // pagina
        $peds = $qry->paginate(50);

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

        // retorna formatado
        return new WooPedidoResource($pedido);
    }

    public function buscarNovos()
    {
        // busca novos pedidos no woo  
        $wps = new WooPedidoService(); 
        $peds = $wps->buscarNovos();

        // retorna formatado
        return WooPedidoResource::collection($peds);
    }

}
