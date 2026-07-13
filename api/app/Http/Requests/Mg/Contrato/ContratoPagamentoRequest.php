<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Recebimento de uma fixação. Sem teto: o recebido pode ficar um pouco acima ou
 * abaixo do líquido (diferencinha de imposto) — a fixação é encerrada pela
 * quitação (tblcontratofixacao.quitado), não por bater no centavo.
 */
class ContratoPagamentoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'data' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'codportador' => ['nullable', 'integer', 'exists:tblportador,codportador'],
            'observacao' => ['nullable', 'string', 'max:120'],
        ];
    }
}
