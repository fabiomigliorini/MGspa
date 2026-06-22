<?php

namespace App\Http\Requests\Mg\Moeda;

use Illuminate\Foundation\Http\FormRequest;

class MoedaUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // O código (PK) não muda na edição — só descrição e símbolo.
        return [
            'descricao' => ['required', 'max:60'],
            'simbolo' => ['required', 'max:5'],
        ];
    }
}
