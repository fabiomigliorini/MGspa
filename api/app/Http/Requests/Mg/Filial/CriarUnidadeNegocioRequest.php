<?php

namespace App\Http\Requests\Mg\Filial;

use Illuminate\Foundation\Http\FormRequest;

class CriarUnidadeNegocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:100'],
            'codfilial' => ['nullable', 'integer', 'exists:tblfilial,codfilial'],
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'Descricao e obrigatoria.',
            'descricao.max' => 'Descricao deve ter no maximo 100 caracteres.',
            'codfilial.exists' => 'Filial informada nao existe.',
        ];
    }
}
