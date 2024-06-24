<?php

namespace Mg\Titulo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TituloAgrupamentoController extends Controller
{

    public function mail(Request $request, $codtituloagrupamento)
    {
        $dest = $request->destnatario??null;
        $ta = TituloAgrupamento::findOrFail($codtituloagrupamento);
        $res = TituloAgrupamentoMailService::mail($ta, $dest);
        return $res;
    }

}
