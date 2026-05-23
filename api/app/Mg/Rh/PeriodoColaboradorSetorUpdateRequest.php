<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class PeriodoColaboradorSetorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'percentualrateio' => 'nullable|numeric|min:0',
            'diastrabalhados' => 'nullable|numeric|min:0',
        ];
    }
}
