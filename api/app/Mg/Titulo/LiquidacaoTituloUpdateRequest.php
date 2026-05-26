<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

class LiquidacaoTituloUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codpessoa'   => 'required|integer|exists:tblpessoa,codpessoa',
            'codportador' => 'required|integer|exists:tblportador,codportador',
            'transacao'   => 'required|date',
            'observacao'  => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório',
        ];
    }
}
