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
        return BoletoService::retornoProcessado();
    }

    public function retornoFalha(Request $request)
    {
        return BoletoService::retornoFalha();
    }

    public function retorno(Request $request, $codportador, $arquivo, $dataretorno)
    {
        return BoletoService::retorno($codportador, $arquivo, $dataretorno);
    }

    public function reprocessarRetorno(Request $request)
    {
        return BoletoRetornoService::reprocessarRetorno();
    }

    public function processarRetorno(Request $request)
    {
        $request->validate([
          'codportador' => ['required', 'integer'],
          'arquivo' => ['required', 'string'],
        ]);
        return BoletoRetornoService::processarRetorno($request->codportador, $request->arquivo);
    }

    public function remessaPendente(Request $request)
    {
        return BoletoService::remessaPendente();
    }

    public function arquivarRemessa(Request $request, $codportador, $arquivo)
    {
        BoletoRemessaService::arquivarRemessa($codportador, $arquivo);
    }

    public function gerarRemessa(Request $request, $codportador)
    {
        $request->validate([
          'codtitulo' => ['required', 'array', 'min:1'],
        ]);
        return BoletoRemessaService::gerarRemessa($codportador, $request->codtitulo);
    }

    public function remessaEnviada(Request $request)
    {
        return BoletoService::remessaEnviada();
    }

    public function remessa(Request $request, int $codportador, int $remessa)
    {
        return BoletoService::remessa($codportador, $remessa);
    }

}
