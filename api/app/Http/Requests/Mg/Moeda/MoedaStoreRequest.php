<?php

namespace App\Http\Requests\Mg\Moeda;

use Illuminate\Foundation\Http\FormRequest;

class MoedaStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->filled('iso')) {
            $this->merge(['iso' => strtoupper($this->input('iso'))]);
        }
    }

    public function rules()
    {
        return [
            'moeda' => ['required', 'max:60'],
            'sigla' => ['required', 'max:5'],
            'iso' => ['required', 'size:3', 'alpha', 'unique:tblmoeda,iso'],
        ];
    }
}
