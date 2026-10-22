<?php

namespace Mg\Negocio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NegocioEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resultado' => ['required', Rule::in(array_keys(NegocioEntrega::RESULTADOS))],
            'motivo' => 'nullable|string|size:1',
            'recebedor' => 'nullable|string|max:100',
            'observacao' => 'nullable|string|max:500',
        ];
    }
}
