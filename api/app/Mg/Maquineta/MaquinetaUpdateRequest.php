<?php

namespace Mg\Maquineta;

use Illuminate\Foundation\Http\FormRequest;

class MaquinetaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'apelido' => 'required|string|max:50',
            'serial' => 'nullable|string|max:50',
            'codfilial' => 'required|integer|exists:tblfilial,codfilial',
            'compartilhada' => 'boolean',
            'codpessoa' => 'nullable|integer|exists:tblpessoa,codpessoa',
        ];
    }
}
