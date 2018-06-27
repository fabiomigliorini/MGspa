<?php

namespace Mg\NFePHP;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\NotaFiscal\NotaFiscal;

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
        $res = NFePHPRepository::sefazStatus($id);
        return response()->json($res, 200);
    }

    public function criar(Request $request, $id)
    {
        $res = NFePHPRepository::criar($id);
        return response($res, 200)->header('Content-Type', 'text/xml');
    }

    public function enviar(Request $request, $id)
    {
        $res = NFePHPRepository::enviar($id);
        return response()->json($res, 200);
    }

    public function enviarSincrono(Request $request, $id)
    {
        $res = NFePHPRepository::enviarSincrono($id);
        return response()->json($res, 200);
    }

    public function consultarRecibo(Request $request, $id)
    {
        $res = NFePHPRepository::consultarRecibo($id);
        return response()->json($res, 200);
    }

    public function cancelar(Request $request, $id)
    {
        $res = NFePHPRepository::cancelar($id, $request->justificativa);
        return response()->json($res, 200);
    }

    public function inutilizar(Request $request, $id)
    {
        $res = NFePHPRepository::inutilizar($id, $request->justificativa);
        return response()->json($res, 200);
    }

    public function consultar(Request $request, $id)
    {
        $res = NFePHPRepository::consultar($id);
        return response()->json($res, 200);
    }

    public function cartaCorrecao(Request $request, $id)
    {
        $res = NFePHPRepository::cartaCorrecao($id, $request->texto);
        return response()->json($res, 200);
    }

    public function danfe(Request $request, $id)
    {
        $res = NFePHPRepository::danfe($id);
        return response()->file($res);
    }

    public function mail(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $res = NFePHPRepositoryMail::mail($nf, $request->destinatario);
        return $res;
        return response()->json($res, 200);
    }

    public function mailCancelamento(Request $request, $id)
    {
        $res = NFePHPRepositoryMail::mailCancelamento($id);
        return response()->json($res, 200);
    }

    public function cscConsulta(Request $request, $id)
    {
        $res = NFePHPRepository::cscConsulta($id);
        return response()->json($res, 200);
    }

    public function xml(Request $request, $id)
    {
        $nf = NotaFiscal::findOrFail($id);
        $xml = NFePHPRepository::xml($nf);
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    public function pendentes(Request $request)
    {
        $ordemReversa = boolval($request->ordemReversa);
        $res = NFePHPRepository::pendentes($ordemReversa);
        return response()->json($res, 200);
    }

}
