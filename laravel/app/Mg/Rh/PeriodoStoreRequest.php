<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class PeriodoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodoinicial' => 'required|date',
            'periodofinal' => 'required|date|after:periodoinicial',
            'diasuteis' => 'nullable|integer|min:0',
            'observacoes' => 'nullable|string',
        ];
    }
}
