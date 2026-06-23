<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContratoNotaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Ordem única dentro do contrato (ignora inativas e a própria na edição).
            'ordem' => [
                'nullable',
                'integer',
                'gte:1',
                Rule::unique('tblcontratonota', 'ordem')
                    ->where('codcontrato', $this->route('codcontrato'))
                    ->whereNull('inativo')
                    ->ignore($this->route('codnota'), 'codcontratonota'),
            ],
            'codnaturezaoperacao' => ['required', 'exists:tblnaturezaoperacao,codnaturezaoperacao'],
            'codpessoanf' => ['nullable', 'exists:tblpessoa,codpessoa'],
            // nota desta cadeia que esta referencia (refNFe). Validada como filha
            // do mesmo contrato no service.
            'codcontratonotapai' => ['nullable', 'exists:tblcontratonota,codcontratonota'],
            'observacaonf' => ['nullable', 'string'],
        ];
    }
}
