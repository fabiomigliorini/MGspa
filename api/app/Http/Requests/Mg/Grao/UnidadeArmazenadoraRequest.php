<?php

namespace App\Http\Requests\Mg\Grao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnidadeArmazenadoraRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'unidadearmazenadora' => ['required', 'string', 'max:60'],
            'tipo' => ['required', Rule::in(['PROPRIO', 'TERCEIRO', 'SILOBAG'])],
            'codpessoa' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'capacidadesacas' => ['nullable', 'numeric', 'gte:0'],
            'observacao' => ['nullable', 'string'],
        ];
    }
}
