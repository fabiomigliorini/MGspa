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
            'isentofethab' => ['nullable', 'boolean'],
        ];
    }
}
