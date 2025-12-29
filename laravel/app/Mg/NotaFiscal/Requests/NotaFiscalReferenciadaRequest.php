<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaFiscalReferenciadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nfechave' => 'required|string|size:44',
        ];
    }

    public function messages(): array
    {
        return [
            'nfechave.size' => 'A chave da NFe deve ter exatamente 44 caracteres.',
        ];
    }
}
