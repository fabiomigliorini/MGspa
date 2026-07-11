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
            // valorrecebido: em US$ o servidor COMPUTA (sacas × preço × cotação);
            // por isso vira nullable aqui e a exigência real é a cotacaorecebido.
            'valorrecebido' => ['nullable', 'numeric', 'gt:0'],
            'cotacaorecebido' => ['nullable', 'numeric', 'gt:0'],
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
            $parcela = ContratoPagamento::with('ContratoFixacao')->find($this->route('codpagamento'));
            $barter = $parcela && $parcela->forma === 'BARTER';
            if (!$barter && !$this->filled('codportador')) {
                $validator->errors()->add(
                    'codportador',
                    'Informe o portador onde o recebimento foi realizado.',
                );
            }

            // Moeda da fixação de origem decide a conversão:
            // - US$: exige a cotação do dia; o valorrecebido em R$ é COMPUTADO no
            //   controller (sacas × preço × cotação), então não é exigido aqui.
            // - BRL: exige o valorrecebido digitado (não há conversão).
            $fixacao = $parcela?->ContratoFixacao;
            $usd = $fixacao && $fixacao->usd;
            if ($usd) {
                if (!$this->filled('cotacaorecebido')) {
                    $validator->errors()->add('cotacaorecebido', 'Informe a cotação do dólar do recebimento.');
                }
            } elseif (!$this->filled('valorrecebido')) {
                $validator->errors()->add('valorrecebido', 'Informe o valor recebido.');
            }
        });
    }
}
