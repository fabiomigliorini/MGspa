<?php

namespace Mg\Pix;

use Illuminate\Http\Request;

use Mg\Negocio\Negocio;
use Mg\Portador\Portador;
use Mg\Pix\GerenciaNet\GerenciaNetService;

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
        $ret = new PixCobResource($cob);
        $ret['qrcode'] = null;
        $ret['qrcodeimagem'] = null;
        if (!empty($cob->locationid)) {
            $qrcode = GerenciaNetService::qrCode($cob->locationid);
            $ret['qrcode'] = $qrcode['qrcode'];
            $ret['qrcodeimagem'] = $qrcode['imagemQrcode'];
        }
        return $ret;
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
        $ret = new PixCobResource($cob);
        $ret['qrcodeimagem'] = null;
        if (!empty($cob->locationid) && $cob->Portador->Banco->numerobanco == 364) {
            $qrcode = GerenciaNetService::qrCode($cob->locationid);
            $ret['qrcode'] = $qrcode['qrcode'];
            $ret['qrcodeimagem'] = $qrcode['imagemQrcode'];
        }
        return $ret;
    }

    public function imprimirQrCode (Request $request, $codpixcob)
    {
        $request->validate([
            'impressora' => ['required', 'string']
        ]);
        $cob = PixCob::findOrFail($codpixcob);
        $pdf = PixService::imprimirQrCode($cob, $request->impressora);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PixCob'.$codpixcob.'.pdf"'
        ]);
    }

    public function pdf (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        $pdf = PixService::pdf($cob);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PixCob'.$codpixcob.'.pdf"'
        ]);
    }


}
