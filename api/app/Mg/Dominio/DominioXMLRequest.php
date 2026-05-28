<?php

namespace Mg\Dominio;

use Illuminate\Foundation\Http\FormRequest;
// use Illuminate\Http\Request;

class DominioXMLRequest extends FormRequest
// class DominioRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
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
            'codfilial' => ['required', 'integer'],
            'modelo' => ['required', 'integer', 'in:55,65'],
            'mes' => ['required', 'date'],
        ];
    }

     /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'codfilial.required' => 'Filial não informada!',
            'mes.required' => 'Mês não informado!',
        ];
    }
}
