<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

class RubricaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => 'required|string|max:200',
            'tipovalor' => 'required|string|size:1|in:P,F,Q',
            'tipocondicao' => 'nullable|string|size:1|in:M,R',
            'valorpadrao' => 'nullable|numeric',
            'valorunitariopadrao' => 'nullable|numeric',
            'recorrente' => 'nullable|boolean',
        ];
    }
}
