<?php

namespace Mg\Boleto;

use Illuminate\Http\Request;
use Mg\MgController;

use App\Http\Requests;

class BoletoController extends MgController
{

    public function retornoPendente(Request $request)
    {
        return BoletoService::retornoPendente();
    }

    public function retornoProcessado(Request $request)
    {
        return BoletoService::retornoProcessado($request->codportador, $request->arquivo);
    }

    public function retorno(Request $request, $codportador, $arquivo, $dataretorno)
    {
        return BoletoService::retorno($codportador, $arquivo, $dataretorno);
    }

    public function processarRetorno(Request $request)
    {
        $request->validate([
          'codportador' => ['required', 'integer'],
          'arquivo' => ['required', 'string'],
        ]);
        return BoletoRetornoService::processarRetorno($request->codportador, $request->arquivo);
    }

}
