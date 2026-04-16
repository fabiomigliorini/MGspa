<?php

namespace Mg\FormaPagamento;

use Illuminate\Foundation\Http\FormRequest;

class FormaPagamentoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'formapagamento' => 'required|string|max:50',
            'formapagamentoecf' => 'nullable|string|max:5',
            'parcelas' => 'nullable|integer',
            'diasentreparcelas' => 'nullable|integer',
            'avista' => 'boolean',
            'boleto' => 'boolean',
            'fechamento' => 'boolean',
            'notafiscal' => 'boolean',
            'entrega' => 'boolean',
            'valecompra' => 'boolean',
            'lio' => 'boolean',
            'pix' => 'boolean',
            'stone' => 'boolean',
            'integracao' => 'boolean',
            'safrapay' => 'boolean',
        ];
    }
}
