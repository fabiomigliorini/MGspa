<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Mg\Negocio\NegocioNotaFiscalResource;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NFePHP\NFePHPService;

// use Mg\Negocio\NegocioResource;
// use Mg\Negocio\Negocio;
// use Mg\NotaFiscal\NotaFiscalService;
// use Mg\PagarMe\PagarMePedidoResource;
// use Mg\Pix\PixService;
// use Mg\PagarMe\PagarMeService;
// use Mg\PagarMe\PagarMePedido;

class PdvNotaFiscalController
{

    public function criar(PdvRequest $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $offline = false;
        if (!empty($request->offline)) {
            $offline = true;
        }
        NFePHPService::criar($nf, $offline);
        return new NegocioNotaFiscalResource($nf);
    }

    public function enviar(PdvRequest $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $res = NFePHPService::enviarSincrono($nf);
        return response()->json([
            'respostaSefaz' =>  $res,
            'nota' => new NegocioNotaFiscalResource($nf),
        ], 200);
    }

    public function consultar(PdvRequest $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $res = NFePHPService::consultar($nf);
        return response()->json([
            'respostaSefaz' =>  $res,
            'nota' => new NegocioNotaFiscalResource($nf),
        ], 200);
    }

    public function cancelar(PdvRequest $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        NFePHPService::cancelar($nf, $request->justificativa);
        return new NegocioNotaFiscalResource($nf);
    }

    public function inutilizar(PdvRequest $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        NFePHPService::inutilizar($nf, $request->justificativa);
        return new NegocioNotaFiscalResource($nf);
    }

    public function imprimir(Request $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $res = NFePHPService::imprimir($nf, $request->impressora);
        return response()->json($res, 200);
    }    

}
