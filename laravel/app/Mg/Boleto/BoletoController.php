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

    public function processarRetorno(Request $request)
    {
        $request->validate([
          'codportador' => ['required', 'integer'],
          'arquivo' => ['required', 'string'],
        ]);
        return BoletoRetornoService::processarRetorno($request->codportador, $request->arquivo);
    }

}
