<?php

namespace Mg\Caixa;

use Illuminate\Foundation\Http\FormRequest;

// Cadastro e alteracao do item do caixa: pessoa obriga a conta contabil (o
// titulo de repasse precisa das duas)
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
            'codfilial' => 'nullable|integer|exists:tblfilial,codfilial',
            'codpessoa' => 'nullable|integer|exists:tblpessoa,codpessoa',
            'codcontacontabil' => 'nullable|required_with:codpessoa|integer|exists:tblcontacontabil,codcontacontabil',
            'ordem' => 'nullable|integer|min:0|max:32000',
        ];
    }

    public function messages(): array
    {
        return [
            'codcontacontabil.required_with' => 'Com parceiro, informe a conta contábil do título de repasse.',
        ];
    }
}
