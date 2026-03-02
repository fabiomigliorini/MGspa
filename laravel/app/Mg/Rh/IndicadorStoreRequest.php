<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class IndicadorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:V,C,S,U',
            'codcolaborador' => 'nullable|integer',
            'codsetor' => 'nullable|integer',
            'codunidadenegocio' => 'nullable|integer',
            'meta' => 'nullable|numeric|min:0',
        ];
    }
}
