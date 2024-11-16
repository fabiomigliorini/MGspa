<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use Mg\Mercos\MercosPedidoService;

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

    public function listagemPedido(Request $request, $alterado_apos = 'ultima')
    {
        $pdv = PdvService::autoriza($request->pdv);
        $ret = MercosPedidoService::listagem();
        return response()->json($ret);
    }
}
