<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class IndicadorLancamentoManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor' => 'required|numeric',
            'descricao' => 'nullable|string|max:200',
        ];
    }
}
