<?php

namespace Mg\NfeTerceiro;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class NfeTerceiroController
{

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
            ],
            'justificativa' => [
                'string',
                // 'required_if' => 'indmanifestacao,210220', // Nao funciona
            ]
        ]);

        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $ret = NfeTerceiroService::manifestacao($nfeTerceiro, $request->indmanifestacao, $request->justificativa);
        return $ret;
    }

}
