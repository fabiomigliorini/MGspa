<?php

namespace Mg\Titulo\BoletoBb;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Titulo\Titulo;

class BoletoBbController extends MgController
{

    public function registrar(Request $request, $codtitulo)
    {
        $titulo = Titulo::findOrFail($codtitulo);
        $ret = BoletoBbService::registrar($titulo);
        return $ret;
    }

}
