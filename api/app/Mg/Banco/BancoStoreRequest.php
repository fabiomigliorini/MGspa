<?php

namespace Mg\Banco;

use Illuminate\Foundation\Http\FormRequest;

class BancoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'banco' => 'required|string|max:50',
            'sigla' => 'nullable|string|max:3',
            'numerobanco' => 'nullable|integer',
        ];
    }
}
