<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class RubricaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => 'sometimes|required|string|max:200',
            'tipovalor' => 'sometimes|required|string|size:1|in:P,F,Q',
            'tipocondicao' => 'nullable|string|size:1|in:M,R',
            'valorpadrao' => 'nullable|numeric',
            'valorunitariopadrao' => 'nullable|numeric',
            'recorrente' => 'nullable|boolean',
        ];
    }
}
