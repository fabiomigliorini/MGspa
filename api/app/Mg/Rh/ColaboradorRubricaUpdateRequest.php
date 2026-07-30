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
            'codrubrica' => 'nullable|integer|exists:tblrubrica,codrubrica',
            'codindicador' => 'nullable|integer|exists:tblindicador,codindicador',
            'codindicadorcondicao' => 'nullable|integer|exists:tblindicador,codindicador',
            'descricao' => 'sometimes|required|string|max:200',
            'observacao' => 'nullable|string',
            'tipovalor' => 'sometimes|required|string|size:1|in:P,F,Q',
            'percentual' => 'nullable|numeric',
            'valorfixo' => 'nullable|numeric',
            'valorunitario' => 'nullable|numeric',
            'quantidade' => 'nullable|numeric',
            'tipocondicao' => 'nullable|string|size:1|in:M,R',
            'concedido' => 'nullable|boolean',
            'recorrente' => 'nullable|boolean',
        ];
    }
}
