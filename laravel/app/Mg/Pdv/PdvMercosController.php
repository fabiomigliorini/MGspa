<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

use Carbon\Carbon;
use Mg\Mercos\MercosPedido;
use Mg\Mercos\MercosPedidoService;
use Mg\Negocio\NegocioResource;
use Mg\Negocio\Negocio;

class PdvMercosController
{

    public function importarPedido(Request $request, $alterado_apos = 'ultima')
    {
        $pdv = PdvService::autoriza($request->pdv);
        DB::beginTransaction();
        if ($alterado_apos != 'ultima') {
            $alterado_apos = Carbon::createFromFormat('Y-m-d H:i:s', $alterado_apos);
        }
        $ret = MercosPedidoService::importarPedidoApos($alterado_apos, $pdv);
        DB::commit();
        return response()->json($ret);
    }

    public function reimportar(Request $request, $codnegocio, $codmercospedido)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $n = Negocio::findOrFail($codnegocio);
        if ($n->codnegociostatus != 1) {
            throw new Exception("Negócio com status diferente de Aberto!", 1);
        }
        $mp = MercosPedido::findOrFail($codmercospedido);
        DB::beginTransaction();
        MercosPedidoService::reimportar($mp);
        DB::commit();
        $n->fresh();
        return new NegocioResource($n);
    }

    public function listagemPedido(Request $request, $alterado_apos = 'ultima')
    {
        $pdv = PdvService::autoriza($request->pdv);
        $ret = MercosPedidoService::listagem();
        return response()->json($ret);
    }

    public function exportarFaturamento(Request $request, $codnegocio, $codmercospedido)
    {
        $n = Negocio::findOrFail($codnegocio);
        MercosPedidoService::exportarFaturamento($n);
        $n->fresh();
        return new NegocioResource($n);
    }

}
