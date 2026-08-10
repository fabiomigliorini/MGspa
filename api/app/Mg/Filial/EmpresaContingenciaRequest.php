<?php

namespace Mg\Filial;

use Illuminate\Foundation\Http\FormRequest;

class EmpresaContingenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // A SEFAZ exige no minimo 15 caracteres no xJust
            'justificativa' => 'required|string|min:15|max:256',
        ];
    }

    public function messages(): array
    {
        return [
            'justificativa.min' => 'A justificativa deve ter pelo menos 15 caracteres.',
        ];
    }
}
