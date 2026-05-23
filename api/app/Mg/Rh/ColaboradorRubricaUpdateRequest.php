<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class ColaboradorRubricaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codperiodocolaboradorsetor' => 'nullable|integer|exists:tblperiodocolaboradorsetor,codperiodocolaboradorsetor',
            'codindicador' => 'nullable|integer|exists:tblindicador,codindicador',
            'codindicadorcondicao' => 'nullable|integer|exists:tblindicador,codindicador',
            'descricao' => 'sometimes|required|string|max:200',
            'tipovalor' => 'sometimes|required|string|size:1|in:P,F',
            'percentual' => 'nullable|numeric',
            'valorfixo' => 'nullable|numeric',
            'tipocondicao' => 'nullable|string|size:1|in:M,R',
            'concedido' => 'nullable|boolean',
            'descontaabsenteismo' => 'nullable|boolean',
            'recorrente' => 'nullable|boolean',
        ];
    }
}
