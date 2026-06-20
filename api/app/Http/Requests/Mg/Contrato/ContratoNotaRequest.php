<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;

class ContratoNotaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'ordem' => ['nullable', 'integer', 'gte:1'],
            'codnaturezaoperacao' => ['required', 'exists:tblnaturezaoperacao,codnaturezaoperacao'],
            'codpessoanf' => ['nullable', 'exists:tblpessoa,codpessoa'],
            // nota desta cadeia que esta referencia (refNFe). Validada como filha
            // do mesmo contrato no service.
            'codcontratonotapai' => ['nullable', 'exists:tblcontratonota,codcontratonota'],
            'observacaonf' => ['nullable', 'string'],
        ];
    }
}
