<?php

namespace App\Http\Requests\Mg\Cultura;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CulturaTributoStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'codtributo' => ['required', 'exists:tbltributo,codtributo'],
            'base' => ['required', Rule::in(['VALOR', 'UNIDADE'])],
            'codunidadereferencia' => [
                'nullable',
                'required_if:base,UNIDADE',
                'exists:tblunidadereferencia,codunidadereferencia',
            ],
            'percentual' => ['required', 'numeric', 'gte:0'],
            'grupofethab' => ['boolean'],
            'funrural' => ['boolean'],
            'ordem' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
