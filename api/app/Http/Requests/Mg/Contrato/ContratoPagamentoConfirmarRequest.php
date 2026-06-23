<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Mg\Contrato\ContratoPagamento;

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

    /**
     * Não se confirma um recebimento sem dizer em qual portador a transação caiu:
     * uma parcela paga sem conta de destino é dinheiro sem rastro. Barter (liquida
     * em insumos, sem conta) é a única isenção — daí o lookup pela forma da parcela.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parcela = ContratoPagamento::find($this->route('codpagamento'));
            $barter = $parcela && $parcela->forma === 'BARTER';
            if (!$barter && !$this->filled('codportador')) {
                $validator->errors()->add(
                    'codportador',
                    'Informe o portador onde o recebimento foi realizado.',
                );
            }
        });
    }
}
