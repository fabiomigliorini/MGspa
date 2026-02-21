<?php

namespace Mg\Filial;

use Illuminate\Foundation\Http\FormRequest;

class SetorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'setor' => 'required|string|max:100',
            'codunidadenegocio' => 'required|integer|exists:tblunidadenegocio,codunidadenegocio',
            'codtiposetor' => 'required|integer|exists:tbltiposetor,codtiposetor',
            'indicadorvendedor' => 'boolean',
            'indicadorcaixa' => 'boolean',
            'indicadorcoletivo' => 'boolean',
        ];
    }
}
