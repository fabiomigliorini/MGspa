<?php

namespace App\Http\Requests\Mg\Embarque;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Embarque\EmbarqueService;

class EmbarqueSincronizarRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'uuid' => ['required', 'string'],
            'etapa' => ['required', Rule::in(EmbarqueService::ETAPAS)],
            'data' => ['required', 'date'],
            'contratos' => ['array'],
            'contratos.*.codcontrato' => ['required', 'exists:tblcontrato,codcontrato'],
            'contratos.*.quantidade' => ['nullable', 'numeric'],
            'origens' => ['array'],
            'origens.*.tipo' => ['required', Rule::in(['SILO', 'TALHAO'])],
            'origens.*.codplantio' => ['nullable', 'exists:tblplantio,codplantio'],
            'pesotara' => ['nullable', 'numeric', 'gte:0'],
            'pesobruto' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}
