<?php

namespace Mg\NfePhp;

use Illuminate\Http\Request;
use Mg\MgController;

use App\Http\Requests;
use Illuminate\Validation\Rule;

class NfePhpController extends MgController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function sefazStatus(Request $request, $id)
    {
        $res = NfePhpRepository::sefazStatus($id);
        return response()->json($res, 200);
    }

    public function criarXml(Request $request, $id)
    {
        $res = NfePhpRepository::criarXml($id);
        return response()->json($res, 200);
    }

    public function assinarXml(Request $request, $id)
    {
        $res = NfePhpRepository::assinarXml($id);
        return response()->json($res, 200);
    }

    public function enviarXml(Request $request, $id)
    {
        $res = NfePhpRepository::enviarXml($id);
        return response()->json($res, 200);
    }

    public function enviarXmlSincrono(Request $request, $id)
    {
        $res = NfePhpRepository::enviarXmlSincrono($id);
        return response()->json($res, 200);
    }

    public function consultarReciboEnvio(Request $request, $id)
    {
        $res = NfePhpRepository::consultarReciboEnvio($id);
        return response()->json($res, 200);
    }

    public function cancelar(Request $request, $id)
    {
        $res = NfePhpRepository::cancelar($id, $request->justificativa);
        return response()->json($res, 200);
    }

    public function inutilizar(Request $request, $id)
    {
        $res = NfePhpRepository::inutilizar($id, $request->justificativa);
        return response()->json($res, 200);
    }

    public function consultar(Request $request, $id)
    {
        $res = NfePhpRepository::consultar($id);
        return response()->json($res, 200);
    }

    public function danfe(Request $request, $id)
    {
        $res = NfePhpRepository::danfe($id);
        return response()->file($res);
    }

    public function cscConsulta(Request $request, $id)
    {
        $res = NfePhpRepository::cscConsulta($id);
        return response()->json($res, 200);
    }

}
