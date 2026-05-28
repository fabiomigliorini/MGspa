<?php

namespace Mg\Cidade\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'estado' => 'required|string|max:100',
            'sigla' => 'required|string|max:10',
            'codigooficial' => 'nullable|integer',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'O nome do estado é obrigatório!',
            'estado.max' => 'O nome do estado deve ter no máximo 100 caracteres!',
            'sigla.required' => 'A sigla do estado é obrigatória!',
            'sigla.max' => 'A sigla deve ter no máximo 10 caracteres!',
            'codigooficial.integer' => 'O código oficial deve ser um número inteiro!',
        ];
    }
}
