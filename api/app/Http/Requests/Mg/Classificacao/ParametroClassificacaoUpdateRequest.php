<?php

namespace App\Http\Requests\Mg\Classificacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParametroClassificacaoUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'parametroclassificacao' => ['required', 'string', 'max:40'],
            'metodo' => ['required', Rule::in(['FATOR', 'NORMALIZADO'])],
            'reduzbase' => ['required', 'boolean'],
        ];
    }
}
