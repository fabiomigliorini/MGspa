<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Mg\Negocio\NegocioNotaFiscalResource;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\NFePHP\NFePHPService;
use Mg\NFePHP\NFePHPMailService;

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
        $res = NFePHPService::cancelar($nf, $request->justificativa);
        return response()->json([
            'respostaSefaz' =>  $res,
            'nota' => new NegocioNotaFiscalResource($nf),
        ], 200);
    }

    public function inutilizar(PdvRequest $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $res = NFePHPService::inutilizar($nf, $request->justificativa);
        return response()->json([
            'respostaSefaz' =>  $res,
            'nota' => new NegocioNotaFiscalResource($nf),
        ], 200);
    }

    public function imprimir(Request $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $res = NFePHPService::imprimir($nf, $request->impressora);
        return response()->json($res, 200);
    }    

    public function excluir(Request $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $res = NotaFiscalService::excluir($nf);
        return response()->json($res, 200);
    }    

    public function mail(PdvRequest $request, $codnotafiscal)
    {
        PdvService::autoriza($request->pdv);
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $res = NFePHPMailService::mail($nf, $request->destinatario);
        return response()->json($res, 200);
    }
}
