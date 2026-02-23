<?php

namespace Mg\Feriado;

use Illuminate\Foundation\Http\FormRequest;

class FeriadoGerarAnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ano' => 'required|integer|min:2015',
        ];
    }
}
