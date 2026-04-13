<?php

namespace Mg\NotaFiscal\Controle;

use Illuminate\Foundation\Http\FormRequest;

class GerarNfceFaltantesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codnegocio_ids' => 'required|array|min:1',
            'codnegocio_ids.*' => 'required|integer',
        ];
    }
}
