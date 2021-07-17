<?php

namespace Mg\NfeTerceiro;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use NFePHP\DA\NFe\Danfe;

use Mg\Dfe\DfeTipo;
use Mg\NFePHP\NFePHPPathService;

class NfeTerceiroController
{

    public function xml (Request $request, $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $dfeTipo = DfeTipo::where(['schemaxml' => 'procNFe_v4.00.xsd'])->firstOrFail();
        $dd = $nfeTerceiro->DistribuicaoDfeS()->where('coddfetipo', $dfeTipo->coddfetipo)->first();
        $path = NFePHPPathService::pathDfeGz($dd);
        $gz = file_get_contents($path);
        $xml = gzdecode($gz);
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    public function danfe (Request $request, $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $dfeTipo = DfeTipo::where(['schemaxml' => 'procNFe_v4.00.xsd'])->firstOrFail();
        $dd = $nfeTerceiro->DistribuicaoDfeS()->where('coddfetipo', $dfeTipo->coddfetipo)->first();
        $path = NFePHPPathService::pathDfeGz($dd);
        $gz = file_get_contents($path);
        $xml = gzdecode($gz);
        $danfe = new Danfe($xml);
        $danfe->debugMode(false);
        $danfe->setDefaultFont('helvetica');
        $pdf = $danfe->render();
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="NfeTerceiro'.$codnfeterceiro.'.pdf"'
        ]);
    }

    public function manifestacao (Request $request, $codnfeterceiro)
    {
        $request->validate([
            'indmanifestacao' => [
                'required',
                'numeric',
                Rule::in([
                    '210200', // OPERACAO REALIZADA
                    '210210', // CIENCIA DA OPERACAO
                    '210220', // OPERACAO DESOCNHECIDA
                    '210240', // OPERACAO NAO REALIZADA
                ])
            ]
        ]);

        if ($request->indmanifestacao == 210240) {
            $request->validate([
                'justificativa' => 'string|required|min:15'
            ]);
        }

        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $ret = NfeTerceiroService::manifestacao($nfeTerceiro, $request->indmanifestacao, $request->justificativa);
        return $ret;
    }

}
