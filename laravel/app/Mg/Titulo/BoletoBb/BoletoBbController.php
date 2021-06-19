<?php

namespace Mg\Titulo\BoletoBb;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloBoleto;
use Mg\Titulo\TituloBoletoResource;

class BoletoBbController extends MgController
{

    public function registrar(Request $request, $codtitulo)
    {
        $titulo = Titulo::findOrFail($codtitulo);
        $tituloBoleto = BoletoBbService::registrar($titulo);
        return new TituloBoletoResource($tituloBoleto);
    }

    public function show(Request $request, $codtitulo, $codtituloboleto)
    {
        $tituloBoleto = TituloBoleto::findOrFail($codtituloboleto);
        return new TituloBoletoResource($tituloBoleto);
    }

    public function consultar(Request $request, $codtitulo, $codtituloboleto)
    {
        $tituloBoleto = TituloBoleto::findOrFail($codtituloboleto);
        $tituloBoleto = BoletoBbService::consultar($tituloBoleto);
        return new TituloBoletoResource($tituloBoleto);
    }

    public function baixar(Request $request, $codtitulo, $codtituloboleto)
    {
        $tituloBoleto = TituloBoleto::findOrFail($codtituloboleto);
        $tituloBoleto = BoletoBbService::baixar($tituloBoleto);
        return new TituloBoletoResource($tituloBoleto);
    }

    public function pdf(Request $request, $codtitulo, $codtituloboleto)
    {
        $tituloBoleto = TituloBoleto::findOrFail($codtituloboleto);
        $pdf = BoletoBbService::pdf($tituloBoleto);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Boleto'.$codtituloboleto.'.pdf"'
        ]);
    }

}
