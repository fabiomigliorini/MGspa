<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;

class ContratoCalculoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'bruto' => ['required', 'numeric'],
            'data' => ['nullable', 'date'],
            'isentofethab' => ['nullable', 'boolean'],
            'funruralvenda' => ['nullable', 'boolean'],
        ];
    }
}
