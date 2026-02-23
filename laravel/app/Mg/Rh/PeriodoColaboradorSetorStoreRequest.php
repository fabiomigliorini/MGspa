<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class PeriodoColaboradorSetorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codsetor' => 'required|integer|exists:tblsetor,codsetor',
            'percentualrateio' => 'nullable|numeric|min:0',
            'diastrabalhados' => 'nullable|numeric|min:0',
        ];
    }
}
