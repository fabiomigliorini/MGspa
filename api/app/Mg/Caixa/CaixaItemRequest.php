<?php

namespace Mg\Caixa;

use Illuminate\Foundation\Http\FormRequest;

// Cadastro e alteracao do item do caixa (cadastro minimo: o nome)
class CaixaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item' => 'required|string|max:50',
        ];
    }
}
