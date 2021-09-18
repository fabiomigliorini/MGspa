<?php

namespace Mg\Negocio;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Mg\Titulo\BoletoBb\BoletoBbService;
use Mg\Titulo\TituloBoletoResource;

class NegocioController extends MgController
{

    public function index (Request $request)
    {
        return response()->json($request->all(), 206);
    }

    public function show (Request $request, $id)
    {
        return response()->json($request->all(), 200);
    }

    public function store (Request $request)
    {
        return response()->json($request->all(), 201);
    }

    public function update (Request $request, $id)
    {
        return response()->json($request->all(), 201);
    }

    public function destroy (Request $request, $id)
    {
       return response()->json($request->all(), 204);
    }

    public function comanda (Request $request, $codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $pdf = NegocioComandaService::pdf($negocio);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda'.$codnegocio.'.pdf"'
        ]);
    }

    public function comandaImprimir (Request $request, $codnegocio)
    {
        $request->validate([
            'impressora' => ['required', 'string']
        ]);
        $negocio = Negocio::findOrFail($codnegocio);
        $pdf = NegocioComandaService::imprimir($negocio, $request->impressora);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda'.$codnegocio.'.pdf"'
        ]);
    }

    public function unificar (Request $request, $codnegocio, $codnegociocomanda)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $negocioComanda = Negocio::findOrFail($codnegociocomanda);
        $negocio = NegocioComandaService::unificar($negocio, $negocioComanda);
        return [
            'codnegocio' => $negocio->codnegocio
        ];
    }

    public function boletoBbPdf (Request $request, $codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $pdf = BoletoBbService::pdfPeloNegocio($negocio);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="BoletosNegocio'.$codnegocio.'.pdf"'
        ]);
    }

    public function boletoBbRegistrar (Request $request, $codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $tituloBoletos = BoletoBbService::registrarPeloNegocio($negocio);
        return TituloBoletoResource::collection($tituloBoletos);
    }

    public function identificarVendedor (Request $request, $codnegocio, $codpessoavendedor)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $pessoaVendedor = \Mg\Pessoa\Pessoa::findOrFail($codpessoavendedor);
        if (!$pessoaVendedor->vendedor) {
            throw new \Exception("\"{$pessoaVendedor->fantasia}\" não é vendedor!", 1);
        }
        $negocio->update([
            'codpessoavendedor' => $codpessoavendedor
        ]);
        return [
            'codnegocio' => $negocio->codnegocio,
            'codpessoavendedor' => $negocio->codpessoavendedor,
            'vendedor' => $pessoaVendedor->fantasia,
        ];
    }



}
