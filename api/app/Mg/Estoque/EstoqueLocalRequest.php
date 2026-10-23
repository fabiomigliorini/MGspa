<?php

namespace Mg\Estoque;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstoqueLocalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estoquelocal' => 'required|string|max:50',
            'sigla' => [
                'required',
                'string',
                'max:3',
                Rule::unique('tblestoquelocal', 'sigla')->ignore($this->route('id'), 'codestoquelocal'),
            ],
            'codfilial' => 'required|integer|exists:tblfilial,codfilial',
            'deposito' => 'boolean',
            'controlaestoque' => 'boolean',
            'codimpressora' => 'nullable|integer|exists:tblimpressora,codimpressora',
        ];
    }

    public function messages(): array
    {
        return [
            'sigla.unique' => 'Já existe um local com esta sigla.',
        ];
    }
}
