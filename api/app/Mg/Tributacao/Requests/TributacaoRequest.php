<?php

namespace Mg\Tributacao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TributacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tributacao' => 'required|string|max:100',
            'aliquotaicmsecf' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'tributacao.required' => 'O nome da tributacao é obrigatório!',
            'tributacao.max' => 'O nome da tributacao deve ter no máximo 100 caracteres!',
            'aliquotaicmsecf.numeric' => 'A alíquota deve ser um valor numérico!',
            'aliquotaicmsecf.min' => 'A alíquota não pode ser negativa!',
            'aliquotaicmsecf.max' => 'A alíquota não pode ser maior que 100!',
        ];
    }
}
