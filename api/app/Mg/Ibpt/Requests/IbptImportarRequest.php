<?php

namespace Mg\Ibpt\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IbptImportarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Os CSVs do IBPT têm ~2 MB por UF; 16 MB dá folga para as próximas versões.
            'arquivo' => ['required', 'file', 'max:16384'],
        ];
    }

    public function messages(): array
    {
        return [
            'arquivo.required' => 'Selecione o arquivo CSV da UF!',
            'arquivo.file' => 'Envio inválido!',
            'arquivo.max' => 'O arquivo é maior que 16 MB!',
        ];
    }
}
