<?php

namespace App\Http\Requests\Mg\Cultura;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TabelaDescontoStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'tipo' => ['required', Rule::in(['UMIDADE', 'IMPUREZA', 'AVARIADOS', 'ESVERDEADOS', 'QUEBRADOS'])],
            'faixainicio' => ['required', 'numeric'],
            'faixafim' => ['required', 'numeric', 'gte:faixainicio'],
            'percentualdesconto' => ['required', 'numeric', 'gte:0'],
        ];
    }
}
