<?php

namespace Mg\Portador;

use Illuminate\Foundation\Http\FormRequest;

class PortadorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'portador' => 'required|string|max:50',
            'codbanco' => 'nullable|integer|exists:tblbanco,codbanco',
            'codfilial' => 'nullable|integer|exists:tblfilial,codfilial',
            'agencia' => 'nullable|integer',
            'agenciadigito' => 'nullable|integer',
            'conta' => 'nullable|integer',
            'contadigito' => 'nullable|integer',
            'emiteboleto' => 'boolean',
            'convenio' => 'nullable|numeric',
            'carteira' => 'nullable|integer',
            'carteiravariacao' => 'nullable|integer',
            'pixdict' => 'nullable|string|max:77',
        ];
    }
}
