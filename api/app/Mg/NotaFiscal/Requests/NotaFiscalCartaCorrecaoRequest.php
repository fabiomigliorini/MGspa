<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaFiscalCartaCorrecaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'texto' => 'required|string|min:15|max:1000',
            'data' => 'nullable|date',
            'lote' => 'nullable|integer',
            'protocolo' => 'nullable|string|max:100',
            'protocolodata' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'texto.min' => 'O texto da carta de correção deve ter no mínimo 15 caracteres.',
            'texto.max' => 'O texto da carta de correção não pode ultrapassar 1000 caracteres.',
        ];
    }
}
