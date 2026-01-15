<?php

namespace Mg\NaturezaOperacao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CfopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'cfop' => 'required|string|max:500',
        ];

        // codcfop é obrigatório apenas na criação (POST)
        if ($this->isMethod('POST')) {
            $rules['codcfop'] = 'required|digits:4|unique:tblcfop,codcfop';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'codcfop.required' => 'O código CFOP é obrigatório!',
            'codcfop.digits' => 'O código CFOP deve ter exatamente 4 dígitos!',
            'codcfop.unique' => 'Este código CFOP já existe!',
            'cfop.required' => 'A descrição do CFOP é obrigatória!',
            'cfop.max' => 'A descrição deve ter no máximo 500 caracteres!',
        ];
    }
}
