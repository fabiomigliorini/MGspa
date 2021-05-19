<?php

namespace Mg\Pix;

use Illuminate\Http\Request;

use Mg\Negocio\Negocio;
use Mg\Portador\Portador;

class PixController
{

    public function criarPixCobNegocio (Request $request, $codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $cob = PixService::criarPixCobNegocio($negocio);
        return new PixCobResource($cob);
    }

    public function transmitirPixCob (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        PixService::transmitirPixCob($cob);
        return new PixCobResource($cob);
    }

    public function consultarPixCob (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        PixService::consultarPixCob($cob);
        return new PixCobResource($cob);
    }

    public function brCodePixCob (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return BrCodeService::montar($cob);
    }

    public function show (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return new PixCobResource($cob);
    }

    public function consultarPix (Request $request)
    {
        $portador = Portador::findOrFail(env('PIX_GERENCIANET_CODPORTADOR'));
        $pixRecebidos = PixService::consultarPix($portador);
        // dd($pixRecebidos);
        return PixResource::collection($pixRecebidos);
    }

    public function detalhes (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return new PixCobResource($cob);
    }


}
