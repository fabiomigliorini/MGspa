<?php

namespace Mg\NotaFiscalTerceiro;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Filial\Filial;

class NotaFiscalTerceiroController extends MgController
{

    public function manifestacao(Request $request)
    {
        $res = NotaFiscalTerceiroRepository::manifestacao($request);
        return response()->json($res, 200);
    }

    public function consultaSefaz(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroRepository::consultaSefaz($filial);
        return response()->json($res, 200);
    }

    public function downloadNotaFiscalTerceiro(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroRepository::downloadNotaFiscalTerceiro($filial, $request->chave);
        return response()->json($res, 200);
    }

    public function listaNotas(Request $request)
    {
        $res = NotaFiscalTerceiroRepository::listaNotas($request);
        return response()->json($res, 200);
    }

    public function ultimaNSU(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroRepository::ultimaNSU($filial);
        return response()->json($res, 200);
    }

    public function buscaNFeTerceiro(Request $request)
    {
        $res = NotaFiscalTerceiroRepository::buscaNfeTerceiro($request->chave);
        return response()->json($res, 200);
    }

    public function listaItem(Request $request)
    {
        $res = NotaFiscalTerceiroRepository::listaItem($request->codnotafiscalterceiro);
        return response()->json($res, 200);
    }

    public function atualizaItem(Request $request)
    {
        $res = NotaFiscalTerceiroRepository::atualizaItem($request);
        return response()->json($res, 200);
    }

    public function atualizaNFe(Request $request)
    {
        $res = NotaFiscalTerceiroRepository::atualizaNFe($request);
        return response()->json($res, 200);
    }

    public function armazenaDadosConsulta(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroRepository::armazenaDadosConsulta($filial);
        return response()->json($res, 200);
    }

    public function armazenaDadosEvento(Request $request)
    {
        $filial = Filial::findOrFail($request->filial);
        $res = NotaFiscalTerceiroRepository::armazenaDadosEvento($filial);
        return response()->json($res, 200);
    }

    public function carregarXml(Request $request, $filial, $chave)
    {
        $filial = Filial::findOrFail($filial);
        $res = NotaFiscalTerceiroRepository::carregarXml($filial, $chave);
        return response()->json($res, 200);
    }

}
