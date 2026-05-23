<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

class TituloAgrupamentoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codpessoa'  => 'required|integer|exists:tblpessoa,codpessoa',
            'emissao'    => 'required|date',
            'observacao' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório',
        ];
    }
}
