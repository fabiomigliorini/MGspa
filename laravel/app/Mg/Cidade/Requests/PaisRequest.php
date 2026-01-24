<?php

namespace Mg\Cidade\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'pais' => 'required|string|max:100',
            'sigla' => 'required|string|max:10',
            'codigooficial' => 'nullable|integer',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'pais.required' => 'O nome do país é obrigatório!',
            'pais.max' => 'O nome do país deve ter no máximo 100 caracteres!',
            'sigla.required' => 'A sigla do país é obrigatória!',
            'sigla.max' => 'A sigla deve ter no máximo 10 caracteres!',
            'codigooficial.integer' => 'O código oficial deve ser um número inteiro!',
        ];
    }
}
