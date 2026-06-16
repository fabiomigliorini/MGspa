<?php

namespace App\Http\Requests\Mg\Cultura;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CulturaUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'cultura' => ['required', Rule::unique('tblcultura')->ignore($this->route('codcultura'), 'codcultura'), 'min:2'],
            'pesosaca' => ['nullable', 'numeric', 'gt:0'],
            'icone' => ['nullable', 'string', 'max:10'],
            'cicloanos' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
