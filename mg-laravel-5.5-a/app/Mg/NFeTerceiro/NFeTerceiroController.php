<?php

namespace Mg\NFeTerceiro;

use Illuminate\Http\Request;

use Mg\MgController;

class NFeTerceiroController extends MgController
{

    public function pesquisarSefaz()
    {
        return NFeTerceiroRepository::pesquisarSefaz();
        // return response()->json($res, 200);
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
