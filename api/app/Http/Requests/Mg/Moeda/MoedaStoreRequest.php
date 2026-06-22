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
        if ($this->filled('moeda')) {
            $this->merge(['moeda' => strtoupper($this->input('moeda'))]);
        }
    }

    public function rules()
    {
        return [
            'moeda' => ['required', 'size:3', 'alpha', 'unique:tblmoeda,moeda'],
            'descricao' => ['required', 'max:60'],
            'simbolo' => ['required', 'max:5'],
        ];
    }
}
