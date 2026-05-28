<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaFiscalDuplicatasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fatura' => 'required|string|max:100',
            'valor' => 'required|numeric|min:0',
            'vencimento' => 'required|date',
        ];
    }
}
