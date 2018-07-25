<?php

namespace Mg\NFeTerceiro;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Filial\Filial;



class NFeTerceiroController extends MgController
{

    public function consultaSefaz(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NFeTerceiroRepository::consultaSefaz($filial);
        return response()->json($res, 200);
    }

    public function downloadNFeTerceiro(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NFeTerceiroRepository::downloadNFeTerceiro($filial, $request->chave);
        return response()->json($res, 200);
    }

    public function listaDFe(Request $request)
    {
        $res = NFeTerceiroRepository::listaDFe($request);
        return response()->json($res, 200);
    }

    public function ultimaNSU(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NFeTerceiroRepository::ultimaNSU($filial);
        return response()->json($res, 200);
    }

    public function buscaNFeTerceiro(Request $request)
    {
        $res = NFeTerceiroRepository::buscaNfeTerceiro($request->chave);
        return response()->json($res, 200);
    }

    public function listaItem(Request $request)
    {
        $res = NFeTerceiroRepository::listaItem($request->codnotafiscalterceiro);
        return response()->json($res, 200);
    }

    // public function armazenaDadosDFe(Request $request)
    // {
    //     $filial = Filial::findOrFail($request->filial);
    //     $res = NFeTerceiroRepository::armazenaDadosDFe($filial);
    //     return response()->json($res, 200);
    // }

    public function carregarXml(Request $request, $filial, $chave)
    {
        $filial = Filial::findOrFail($filial);
        $res = NFeTerceiroRepository::carregarXml($filial, $chave);
        return response()->json($res, 200);
    }

}
