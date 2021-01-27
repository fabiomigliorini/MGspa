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


}
