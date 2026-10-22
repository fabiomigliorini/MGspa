<?php

namespace Mg\Impressora;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImpressoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'impressora' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_.-]+$/',
                Rule::unique('tblimpressora', 'impressora')->ignore($this->route('codimpressora'), 'codimpressora'),
            ],
            'descricao' => 'required|string|max:100',
            'tipo' => ['required', Rule::in(array_keys(Impressora::TIPOS))],
        ];
    }

    public function messages(): array
    {
        return [
            'impressora.regex' => 'O nome da fila só aceita letras, números, ponto, hífen e sublinhado (igual ao CUPS).',
            'impressora.unique' => 'Já existe uma impressora com este nome de fila.',
        ];
    }
}
