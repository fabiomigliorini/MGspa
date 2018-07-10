<?php

namespace Mg\NFeTerceiro;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Filial\Filial;



class NFeTerceiroController extends MgController
{

    public function consultaDfe(Request $request, $id)
    {
        $filial = Filial::findOrFail($id);
        $res = NFeTerceiroRepository::consultaDfe($filial);
        return response()->json($res, 200);
    }

    public function listaNfeTerceiro()
    {
        return NFeTerceiroRepository::listaNfeTerceiro();
        // return response()->json($res, 200);
    }

    public function detalhesNfeTerceiro()
    {
        return NFeTerceiroRepository::detalhesNfeTerceiro();
        // return response()->json($res, 200);
    }

    // public function sefazStatus(Request $request, $id)
    // {
    //     $filial = Filial::findOrFail($id);
    //     $res = NFePHPRepository::sefazStatus($filial);
    //     return response()->json($res, 200);
    // }


}
