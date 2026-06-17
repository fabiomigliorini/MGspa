<?php

namespace App\Http\Requests\Mg\Cultura;

use Illuminate\Foundation\Http\FormRequest;

class VariedadeStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'variedade' => ['required', 'min:2'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
        ];
    }
}
