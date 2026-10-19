<?php

namespace Mg\Pdv;

use Illuminate\Foundation\Http\FormRequest;

class PdvUnificarComandaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'codpessoa' => 'nullable|integer',
            'codpessoavendedor' => 'nullable|integer',
        ];
    }
}
