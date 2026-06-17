<?php

namespace App\Http\Requests\Mg\Fazenda;

use Illuminate\Foundation\Http\FormRequest;

class TalhaoUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'talhao' => ['required', 'min:1'],
            'codfazenda' => ['required', 'exists:tblfazenda,codfazenda'],
            'area' => ['required', 'numeric', 'gt:0'],
            'geometria' => ['nullable', 'array'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'cor' => ['nullable', 'string', 'max:9'],
        ];
    }
}
