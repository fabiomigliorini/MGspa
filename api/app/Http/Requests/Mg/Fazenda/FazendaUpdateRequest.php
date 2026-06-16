<?php

namespace App\Http\Requests\Mg\Fazenda;

use Illuminate\Foundation\Http\FormRequest;

class FazendaUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'fazenda' => ['required', 'min:2'],
        ];
    }
}
