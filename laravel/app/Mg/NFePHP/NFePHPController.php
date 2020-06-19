<?php

namespace Mg\NFePHP;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Filial\Filial;

class NFePHPController extends MgController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function sefazStatus(Request $request, $id)
    {
        $filial = Filial::findOrFail($id);
        $res = NFePHPService::sefazStatus($filial);
        return response()->json($res, 200);
    }

    public function criar(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $offline = false;
        if (!empty($request->offline)) {
            $offline = true;
        }
        $res = NFePHPService::criar($nf, $offline);
        return response($res, 200)->header('Content-Type', 'text/xml');
    }

    public function enviar(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::enviar($nf);
        return response()->json($res, 200);
    }

    public function enviarSincrono(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::enviarSincrono($nf);
        return response()->json($res, 200);
    }

    public function consultarRecibo(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::consultarRecibo($nf);
        return response()->json($res, 200);
    }

    public function cancelar(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::cancelar($nf, $request->justificativa);
        return response()->json($res, 200);
    }

    public function inutilizar(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::inutilizar($nf, $request->justificativa);
        return response()->json($res, 200);
    }

    public function consultar(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::consultar($nf);
        return response()->json($res, 200);
    }

    public function cartaCorrecao(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::cartaCorrecao($nf, $request->texto);
        return response()->json($res, 200);
    }

    public function danfe(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::danfe($nf);
        return response()->file($res);
    }

    public function imprimir(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPService::imprimir($nf, $request->impressora);
        return response()->json($res, 200);
    }

    public function mail(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPMailService::mail($nf, $request->destinatario);
        return $res;
        return response()->json($res, 200);
    }

    public function mailCancelamento(Request $request, $id)
    {
        $res = NFePHPMailService::mailCancelamento($id);
        return response()->json($res, 200);
    }

    public function cscConsulta(Request $request, $id)
    {
        $filial = Filial::findOrFail($id);
        $res = NFePHPService::cscConsulta($filial);
        return response()->json($res, 200);
    }

    public function xml(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $xml = NFePHPService::xml($nf);
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    public function pendentes(Request $request)
    {
        $per_page = $request->per_page??50;
        $current_page = $request->current_page??1;
        $desc = boolval($request->desc);
        $res = NFePHPRoboService::pendentes($per_page, $current_page, $desc);
        return response()->json($res, 200);
    }

    public function resolver(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPRoboService::resolver($nf);
        return response()->json($res, 200);
    }

    public function resolverPendentes(Request $request)
    {
        $per_page = $request->per_page??10;
        $current_page = $request->current_page??1;
        $desc = boolval($request->desc);
        $res = NFePHPRoboService::resolverPendentes($per_page, $current_page, $desc);
        return response()->json($res, 200);
    }

}
