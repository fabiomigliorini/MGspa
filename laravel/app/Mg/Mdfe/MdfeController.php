<?php

namespace Mg\Mdfe;

use Illuminate\Http\Request;

use Mg\NotaFiscal\NotaFiscal;

class MdfeController
{

    // Cria registro de MDFE baseado nos dados de uma Nota Fiscal
    public function criarDaNotaFiscal (Request $request, $codnotafiscal)
    {
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $mdfe = MdfeService::criarDaNotaFiscal($nf);
        return $mdfe;
    }

    // Cria Arquivo XML da MDFe
    public function criarXml (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        $xml = MdfeNfePhpService::criarXml($mdfe);
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    // Cria Arquivo XML da MDFe
    public function enviar (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        $ret = MdfeNfePhpService::enviar($mdfe);
        return $ret;
    }

    // Consultar Recibo na Sefaz
    public function consultarRecibo (Request $request, $codmdfe, $codmdfeenviosefaz = null)
    {
        if (!empty($codmdfeenviosefaz)) {
            $mdfeEnvioSefaz = MdfeEnvioSefaz::findOrFail($codmdfeenviosefaz);
        } else {
            $mdfe = Mdfe::findOrFail($codmdfe);
            $mdfeEnvioSefaz = $mdfe->MdfeEnvioSefazS()->orderBy('criacao', 'DESC')->first();
        }
        $ret = MdfeNfePhpService::consultarRecibo($mdfeEnvioSefaz);
        return $ret;
    }

    // Consultar MDFe na Sefaz
    public function consultar (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        $ret = MdfeNfePhpService::consultar($mdfe);
        return $ret;
    }


}
