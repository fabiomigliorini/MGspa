<?php

namespace Mg\Cidade\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'cidade' => 'required|string|max:100',
            'sigla' => 'nullable|string|max:10',
            'codigooficial' => 'nullable|integer',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'cidade.required' => 'O nome da cidade é obrigatório!',
            'cidade.max' => 'O nome da cidade deve ter no máximo 100 caracteres!',
            'sigla.max' => 'A sigla deve ter no máximo 10 caracteres!',
            'codigooficial.integer' => 'O código oficial deve ser um número inteiro!',
        ];
    }
}
