<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;

class ContratoAnexoStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
            'label' => ['nullable', 'string', 'max:120'],
        ];
    }
}
