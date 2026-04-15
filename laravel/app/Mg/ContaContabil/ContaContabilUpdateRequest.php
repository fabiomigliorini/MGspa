<?php

namespace Mg\ContaContabil;

use Illuminate\Foundation\Http\FormRequest;

class ContaContabilUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contacontabil' => 'required|string|max:100',
            'numero' => 'nullable|string|max:15',
        ];
    }
}
