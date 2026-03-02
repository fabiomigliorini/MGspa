<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class PeriodoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodoinicial'        => 'sometimes|date',
            'periodofinal'          => 'sometimes|date',
            'diasuteis'             => 'sometimes|integer|min:0',
            'observacoes'           => 'nullable|string',
            'percentualmaxdesconto' => 'sometimes|nullable|numeric|min:0|max:100',
        ];
    }
}
