<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaFiscalPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|integer',
            'descricao' => 'nullable|string|max:100',
            'valorpagamento' => 'required|numeric|min:0',
            'avista' => 'required|boolean',
            'troco' => 'nullable|numeric|min:0',

            // Cartão
            'bandeira' => 'nullable|integer',
            'autorizacao' => 'nullable|string|max:100',
            'integracao' => 'nullable|boolean',
            'codpessoa' => 'nullable|integer|exists:tblpessoa,codpessoa',
        ];
    }
}
