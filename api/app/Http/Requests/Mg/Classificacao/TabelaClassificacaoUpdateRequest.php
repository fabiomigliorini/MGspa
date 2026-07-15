<?php

namespace App\Http\Requests\Mg\Classificacao;

use Illuminate\Foundation\Http\FormRequest;

class TabelaClassificacaoUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'tabelaclassificacao' => ['required', 'string', 'max:60'],
            'itens' => ['array'],
            'itens.*.codparametroclassificacao' => ['required', 'exists:tblparametroclassificacao,codparametroclassificacao'],
            'itens.*.ordem' => ['nullable', 'integer'],
            'itens.*.tolerancia' => ['nullable', 'numeric', 'gte:0'],
            'itens.*.fator' => ['nullable', 'numeric', 'gte:0'],
            'itens.*.desagio' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
        ];
    }
}
