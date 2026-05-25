<?php

namespace Mg\Pessoa;

use Illuminate\Foundation\Http\FormRequest;

class GrupoClienteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grupocliente' => 'required|string|max:50',
        ];
    }
}
