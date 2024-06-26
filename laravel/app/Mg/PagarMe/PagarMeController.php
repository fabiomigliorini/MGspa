<?php

namespace Mg\PagarMe;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mg\PagarMe\PagarMePostPedidoRequest;
//use Illuminate\Support\Facades\Auth;
//use App\Http\Requests;
//use DB;

use Mg\MgController;

class PagarMeController extends MgController
{

    public function webhook(Request $request)
    {
        Log::info('Recebendo Webhook PagarMe');
        $arquivo = PagarMeJsonService::salvar($request->getContent());
        PagarMeWebhookJob::dispatch($arquivo);
        return response()->json([
            'success'=>true,
            'arquivo'=>$arquivo
        ], 200);
    }

    public function criarPedido(PagarMePostPedidoRequest $request)
    {
        $data = (object) $request->all();
        // return response()->json($data, 201);
        $ret = PagarMeService::cancelarPedidosAbertosPos($data->codpagarmepos);
        $ped = PagarMeService::criarPedido(
            $data->codfilial,
            $data->codpagarmepos,
            $data->tipo,
            $data->valor,
            $data->valorjuros,
            $data->valorjuros + $data->valor,
            $data->valorparcela,
            $data->parcelas,
            $data->jurosloja,
            $data->descricao,
            $data->codnegocio,
            null,
            $data->codpessoa
        );
        // PagarMeWebhookJob::dispatch($arquivo);
        return response()->json([
            'success'=>true,
            'pedido'=>$ped->getAttributes()
        ], 201);
    }

    public function cancelarPedido(request $request, $codpagarmepedido)
    {
        $ped = PagarMePedido::findOrFail($codpagarmepedido);
        PagarMeService::cancelarPedido($ped);
        return response()->json([
            'success'=>true,
            'pedido'=>$ped->getAttributes()
        ], 200);
    }

    public function consultarPedido(request $request, $codpagarmepedido)
    {
        $ped = PagarMePedido::findOrFail($codpagarmepedido);
        PagarMeService::consultarPedido($ped);
        return response()->json([
            'success'=>true,
            'pedido'=>$ped->getAttributes()
        ], 200);
    }

}
