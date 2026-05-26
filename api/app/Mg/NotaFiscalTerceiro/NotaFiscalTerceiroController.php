<?php

namespace Mg\NotaFiscalTerceiro;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Filial\Filial;

class NotaFiscalTerceiroController extends MgController
{

    public function manifestacao(Request $request)
    {
        $res = NotaFiscalTerceiroService::manifestacao($request);
        return response()->json($res, 200);
    }

    public function consultaSefaz(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroService::consultaSefaz($filial);
        return response()->json($res, 200);
    }

    public function downloadNotaFiscalTerceiro(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroService::downloadNotaFiscalTerceiro($filial, $request->chave);
        return response()->json($res, 200);
    }

    public function listaNotas(Request $request)
    {
        $res = NotaFiscalTerceiroService::listaNotas($request);
        return response()->json($res, 200);
    }

    public function ultimaNSU(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroService::ultimaNSU($filial);
        return response()->json($res, 200);
    }

    public function buscaNFeTerceiro(Request $request)
    {
        $res = NotaFiscalTerceiroService::buscaNfeTerceiro($request->chave);
        return response()->json($res, 200);
    }

    public function listaItem(Request $request)
    {
        $res = NotaFiscalTerceiroService::listaItem($request->codnotafiscalterceiro);
        return response()->json($res, 200);
    }

    public function atualizaItem(Request $request)
    {
        $res = NotaFiscalTerceiroService::atualizaItem($request);
        return response()->json($res, 200);
    }

    public function atualizaNFe(Request $request)
    {
        $res = NotaFiscalTerceiroService::atualizaNFe($request);
        return response()->json($res, 200);
    }

    public function armazenaDadosConsulta(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroService::armazenaDadosConsulta($filial);
        return response()->json($res, 200);
    }

    // public function armazenaDadosEvento(Request $request)
    // {
    //     $filial = Filial::findOrFail($request->filial);
    //     $res = NotaFiscalTerceiroRegPassagemService::armazenaDadosEvento($filial);
    //     return response()->json($res, 200);
    // }

    // public function carregarXml(Request $request, $filial, $chave)
    // {
    //     $filial = Filial::findOrFail($filial);
    //     $res = NotaFiscalTerceiroService::carregarXml($filial, $chave);
    //     return response()->json($res, 200);
    // }

}
