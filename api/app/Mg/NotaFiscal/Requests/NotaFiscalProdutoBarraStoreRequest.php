<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaFiscalProdutoBarraStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codprodutobarra' => 'required|integer|exists:tblprodutobarra,codprodutobarra',
        ];
    }
}
