<?php

namespace App\Http\Requests\Mg\Fazenda;

use Illuminate\Foundation\Http\FormRequest;

class FazendaStoreRequest extends FormRequest
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
