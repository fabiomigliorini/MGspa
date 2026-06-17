<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;

class ContratoPagamentoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'data' => ['required', 'date'],             // data prevista
            'valor' => ['required', 'numeric', 'gt:0'],  // valor previsto
            'modo' => ['nullable', 'in:SACAS,VALOR'],
            'sacas' => ['nullable', 'numeric', 'gte:0'],
            'codportador' => ['nullable', 'exists:tblportador,codportador'],
            'datarecebido' => ['nullable', 'date'],
            'valorrecebido' => ['nullable', 'numeric', 'gte:0'],
            'observacao' => ['nullable', 'string'],
        ];
    }
}
