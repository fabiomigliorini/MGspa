<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Mg\Contrato\ContratoService;

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
            'forma' => ['nullable', 'in:CONTA,BARTER'],  // liquidação: em conta vs barter (insumos)
            'modo' => ['nullable', 'in:SACAS,VALOR'],
            // modo=SACAS exige a quantidade em sacas correspondente.
            'sacas' => ['nullable', 'numeric', 'gt:0', 'required_if:modo,SACAS'],
            'codportador' => ['nullable', 'exists:tblportador,codportador'],
            // Se há data de recebimento, há valor recebido (> 0). Sem ordem entre
            // previsto e recebido: antecipação (receber antes do vencimento) é válida.
            'datarecebido' => ['nullable', 'date'],
            'valorrecebido' => ['nullable', 'numeric', 'gt:0', 'required_with:datarecebido'],
            'observacao' => ['nullable', 'string'],
        ];
    }

    /**
     * Teto financeiro (backend = fonte de verdade): a soma dos pagamentos ativos
     * + este não pode passar do valor BRUTO fixado (Σ quantidade × precoreal).
     * Sem fixação (teto 0) não trava — permite adiantamento antes de fixar.
     * No modo SACAS, valida também as sacas contra as sacas fixadas.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $codcontrato = (int) $this->route('codcontrato');
            $codpagamento = $this->route('codpagamento');
            $exceto = $codpagamento !== null ? (int) $codpagamento : null;

            $teto = ContratoService::valorFixadoBruto($codcontrato);
            if ($teto > 0) {
                $novo = (float) $this->input('valor');
                $jaPago = ContratoService::valorPago($codcontrato, $exceto);
                if ($jaPago + $novo > $teto + 0.005) {
                    $saldo = max(0, $teto - $jaPago);
                    $validator->errors()->add(
                        'valor',
                        'Excede o saldo a pagar do contrato (R$ ' . number_format($saldo, 2, ',', '.') . ').',
                    );
                }
            }

            if ($this->input('modo') === 'SACAS') {
                $tetoSc = ContratoService::sacasFixadas($codcontrato);
                if ($tetoSc > 0) {
                    $novoSc = (float) $this->input('sacas');
                    $jaSc = ContratoService::sacasPagas($codcontrato, $exceto);
                    if ($jaSc + $novoSc > $tetoSc + 1e-6) {
                        $saldoSc = max(0, $tetoSc - $jaSc);
                        $validator->errors()->add(
                            'sacas',
                            'Excede as sacas fixadas (saldo ' . number_format($saldoSc, 0, ',', '.') . ' sc).',
                        );
                    }
                }
            }
        });
    }
}
