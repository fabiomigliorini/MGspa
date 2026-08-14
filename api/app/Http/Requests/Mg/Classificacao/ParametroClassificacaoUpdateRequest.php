<?php

namespace App\Http\Requests\Mg\Classificacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Classificacao\ParametroClassificacao;

class ParametroClassificacaoUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'parametroclassificacao' => ['required', 'string', 'max:40'],
            'metodo' => ['required', Rule::in(ParametroClassificacao::METODOS)],
            'reduzbase' => ['required', 'boolean'],
            'ordem' => ['required', 'integer', 'min:0'],
            // tolerância e deságio são percentuais; o fator é a taxa por ponto
            // (1,5 = 1,5% por ponto acima da tolerância), também limitada a 100.
            'tolerancia' => ['required', 'numeric', 'gte:0', 'lte:100'],
            'fator' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'desagio' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
        ];
    }
}
