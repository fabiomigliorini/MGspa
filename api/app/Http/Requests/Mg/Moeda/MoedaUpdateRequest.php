<?php

namespace App\Http\Requests\Mg\Moeda;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoedaUpdateRequest extends FormRequest
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
        $codmoeda = $this->route('moeda');

        return [
            'moeda' => ['required', 'max:60'],
            'sigla' => ['required', 'max:5'],
            'iso' => [
                'required',
                'size:3',
                'alpha',
                Rule::unique('tblmoeda', 'iso')->ignore($codmoeda, 'codmoeda'),
            ],
        ];
    }
}
