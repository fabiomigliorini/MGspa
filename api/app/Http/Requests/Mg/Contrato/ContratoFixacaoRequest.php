<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContratoFixacaoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'data' => ['required', 'date'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'preco' => ['required', 'numeric', 'gte:0'],
            'moeda' => ['nullable', Rule::in(['BRL', 'USD'])],
            'dolar' => ['nullable', 'numeric', 'gt:0'],
            // Snapshot dos impostos digitado/ajustado no modal. O líquido oficial
            // é recalculado no controller a partir dessas linhas (não confia no
            // total que veio do cliente). Ausente = calcula on-the-fly na leitura.
            'tributos' => ['nullable', 'array'],
            'tributos.*.codtributo' => ['nullable', 'integer'],
            'tributos.*.codigo' => ['nullable', 'string', 'max:20'],
            'tributos.*.descricao' => ['nullable', 'string', 'max:100'],
            'tributos.*.base' => ['required_with:tributos', Rule::in(['UNIDADE', 'VALOR'])],
            'tributos.*.percentual' => ['required_with:tributos', 'numeric', 'gte:0'],
            'tributos.*.upf' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}
