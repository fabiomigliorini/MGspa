<?php

namespace Mg\Feriado;

use Illuminate\Foundation\Http\FormRequest;

class FeriadoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => 'required|date',
            'feriado' => 'required|string|max:100',
        ];
    }
}
