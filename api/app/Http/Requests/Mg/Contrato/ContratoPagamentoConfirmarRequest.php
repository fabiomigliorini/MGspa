<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;

class ContratoPagamentoConfirmarRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'datarecebido' => ['required', 'date'],
            'valorrecebido' => ['required', 'numeric', 'gt:0'],
            'codportador' => ['nullable', 'exists:tblportador,codportador'],
        ];
    }
}
