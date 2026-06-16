<?php

namespace App\Http\Requests\Mg\Cultura;

use Illuminate\Foundation\Http\FormRequest;

class CulturaStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'cultura' => ['required', 'unique:tblcultura', 'min:2'],
            'pesosaca' => ['nullable', 'numeric', 'gt:0'],
            'icone' => ['nullable', 'string', 'max:10'],
            'cicloanos' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
