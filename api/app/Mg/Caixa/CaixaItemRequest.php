<?php

namespace Mg\Caixa;

use Illuminate\Foundation\Http\FormRequest;

// Cadastro e alteracao do item do caixa: o nome e o modo; a maquineta de
// parceiro (modo M) leva a pessoa, a filial e a conta contabil do titulo
class CaixaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item' => 'required|string|max:50',
            'modo' => 'required|in:C,M',
            'codpessoa' => 'nullable|required_if:modo,M|integer|exists:tblpessoa,codpessoa',
            'codfilial' => 'nullable|required_if:modo,M|integer|exists:tblfilial,codfilial',
            'codcontacontabil' => 'nullable|required_if:modo,M|integer|exists:tblcontacontabil,codcontacontabil',
        ];
    }

    public function messages(): array
    {
        return [
            'codpessoa.required_if' => 'Informe o parceiro da maquineta.',
            'codfilial.required_if' => 'Informe a filial da maquineta.',
            'codcontacontabil.required_if' => 'Informe a conta contábil do título.',
        ];
    }
}
