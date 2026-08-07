<?php

namespace Mg\Inutilizacao;

use Illuminate\Foundation\Http\FormRequest;

class InutilizacaoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codfilial' => 'required|integer|exists:tblfilial,codfilial',
            'modelo' => 'required|integer|in:55,65',
            'serie' => 'required|integer|min:0',
            'numeroinicial' => 'required|integer|min:1',
            'numerofinal' => 'required|integer|min:1|gte:numeroinicial',
            // 15 caracteres e o minimo exigido pela SEFAZ
            'justificativa' => 'required|string|min:15|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'numerofinal.gte' => 'O número final da faixa não pode ser menor que o inicial.',
            'justificativa.min' => 'A justificativa deve ter pelo menos 15 caracteres.',
            'modelo.in' => 'Só é possível inutilizar numeração de NF-e (55) ou NFC-e (65).',
        ];
    }
}
