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
        $ret = MdfeService::criarDaNotaFiscal($nf);
        return $ret;
    }


}
