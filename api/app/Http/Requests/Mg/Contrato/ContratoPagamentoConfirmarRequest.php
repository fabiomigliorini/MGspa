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
        // valorrecebido gt:0 já garante recebimento não-nulo. Não se exige
        // datarecebido >= data prevista: antecipação (receber antes do vencimento)
        // é legítima, então não há ordem obrigatória entre previsto e recebido.
        return [
            'datarecebido' => ['required', 'date'],
            'valorrecebido' => ['required', 'numeric', 'gt:0'],
            'codportador' => ['nullable', 'exists:tblportador,codportador'],
        ];
    }
}
