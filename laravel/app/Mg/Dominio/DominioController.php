<?php

namespace Mg\Dominio;

use Illuminate\Http\Request;
use Mg\MgController;

use Carbon\Carbon;


class DominioController extends MgController
{

    // public function estoque(Request $request)
    public function estoque(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioService::estoque($codfilial, $mes);
    }

    public function produto(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioService::produto($codfilial, $mes);
    }

    public function pessoa(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioService::pessoa($codfilial, $mes);
    }

    public function entrada(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioService::entrada($codfilial, $mes);
    }

}
