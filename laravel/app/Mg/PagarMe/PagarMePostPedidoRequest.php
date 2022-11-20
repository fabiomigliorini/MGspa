<?php

namespace Mg\PagarMe;

use Illuminate\Foundation\Http\FormRequest;
// use Illuminate\Http\Request;

class PagarMePostPedidoRequest extends FormRequest
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
            'codnegocio' => ['required', 'integer'],
            'codpessoa' => ['required', 'integer'],
            'codpagarmepos' => ['required', 'integer'],
            'valor' => ['required', 'numeric'],
            'tipo' => ['required', 'integer'],
            'parcelas' => ['required', 'integer'],
            'jurosloja' => ['required', 'boolean'],
            'descricao' => ['required', 'string'],
            'codfilial' => ['required', 'integer'],
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
     /*
    public function messages()
    {
        return [
            'codfilial.required' => 'Filial não informada!',
            'mes.required' => 'Mês não informado!',
        ];
    }
    */
}
