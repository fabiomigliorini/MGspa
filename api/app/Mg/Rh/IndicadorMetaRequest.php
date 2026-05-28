<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class IndicadorMetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meta' => 'nullable|numeric|min:0',
        ];
    }
}
