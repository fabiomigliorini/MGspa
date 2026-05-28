<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

class TituloUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codtipotitulo'      => 'required|integer|exists:tbltipotitulo,codtipotitulo',
            'codfilial'          => 'required|integer|exists:tblfilial,codfilial',
            'codpessoa'          => 'required|integer|exists:tblpessoa,codpessoa',
            'codcontacontabil'   => 'required|integer|exists:tblcontacontabil,codcontacontabil',
            'codportador'        => 'nullable|integer|exists:tblportador,codportador',
            'numero'             => 'nullable|string|max:20',
            'fatura'             => 'nullable|string|max:50',
            'valor'              => 'nullable',
            'transacao'          => 'nullable|date',
            'emissao'            => 'nullable|date',
            'vencimento'         => 'nullable|date',
            'vencimentooriginal' => 'nullable|date',
            'gerencial'          => 'boolean',
            'observacao'         => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório',
            'date' => 'Data inválida',
            'exists' => 'Registro inexistente',
        ];
    }
}
