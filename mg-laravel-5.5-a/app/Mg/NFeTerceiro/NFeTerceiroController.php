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

    public function downloadNFeTerceiro(Request $request, $filial, $chave )
    {
        // $chave = NFeTerceiroDistribuicaoDfe::findOrFail($id);
        // $res = NFeTerceiroRepository::downloadNFeTerceiro($chave);
        $filial = Filial::findOrFail($filial);
        $res = NFeTerceiroRepository::downloadNFeTerceiro($filial, $chave);
        return response()->json($res, 200);
    }

    public function carregarXml(Request $request, $filial, $chave )
    {
        $filial = Filial::findOrFail($filial);
        $res = NFeTerceiroRepository::carregarXml($filial, $chave);
        return response()->json($res, 200);
    }

    public function listaNfeTerceiro()
    {
        $res = NFeTerceiroRepository::listaNfeTerceiro();
        return response()->json($res, 200);
    }


    // public function sefazStatus(Request $request, $id)
    // {
    //     $filial = Filial::findOrFail($id);
    //     $res = NFePHPRepository::sefazStatus($filial);
    //     return response()->json($res, 200);
    // }


}
