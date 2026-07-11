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
            // Fixação de origem da parcela (1 fixação : N parcelas). Dirige moeda/
            // preço/unidade; a checagem de "pertence ao contrato" fica no withValidator.
            'codcontratofixacao' => ['nullable', 'integer', 'exists:tblcontratofixacao,codcontratofixacao'],
            // Cotação USD->BRL prevista (só p/ fixação em moeda estrangeira).
            'cotacao' => ['nullable', 'numeric', 'gt:0'],
            'cotacaorecebido' => ['nullable', 'numeric', 'gt:0'],
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

            // Dois regimes EXCLUSIVOS de teto (não empilham):
            $codfixacao = $this->input('codcontratofixacao');
            if ($codfixacao !== null && $codfixacao !== '') {
                // Novo modelo: a parcela pertence a uma fixação. Teto = sacas DESTA
                // fixação (neutro de moeda; funciona igual p/ BRL e US$).
                $fixacao = \Mg\Contrato\ContratoFixacao::whereNull('inativo')->find((int) $codfixacao);
                if (!$fixacao || (int) $fixacao->codcontrato !== $codcontrato) {
                    $validator->errors()->add('codcontratofixacao', 'Fixação inválida para este contrato.');
                } else {
                    $usd = $fixacao->usd;
                    if ($usd && $this->input('modo') !== 'SACAS') {
                        $validator->errors()->add('modo', 'Fixação em US$: a parcela deve ser em sacas.');
                    }
                    if ($usd && !$this->filled('cotacao')) {
                        $validator->errors()->add('cotacao', 'Informe a cotação do dólar da parcela.');
                    }
                    $novoSc = (float) $this->input('sacas');
                    $jaSc = ContratoService::sacasParceladasFixacao((int) $codfixacao, $exceto);
                    if ($novoSc > 0 && $jaSc + $novoSc > (float) $fixacao->quantidade + 1e-6) {
                        $saldoSc = max(0, (float) $fixacao->quantidade - $jaSc);
                        $validator->errors()->add(
                            'sacas',
                            'Excede as sacas da fixação (saldo ' . number_format($saldoSc, 0, ',', '.') . ' sc).',
                        );
                    }
                }
            } else {
                // Legado (parcela sem vínculo): teto em R$ (bruto fixado) + sacas do contrato.
                $teto = ContratoService::valorFixadoBruto($codcontrato);
                $jaPago = ContratoService::valorPago($codcontrato, $exceto);
                if ($teto > 0 && $jaPago + (float) $this->input('valor') > $teto + 0.005) {
                    $saldo = max(0, $teto - $jaPago);
                    $validator->errors()->add(
                        'valor',
                        'Excede o saldo a pagar do contrato (R$ ' . number_format($saldo, 2, ',', '.') . ').',
                    );
                }
                if ($this->input('modo') === 'SACAS') {
                    $tetoSc = ContratoService::sacasFixadas($codcontrato);
                    $jaSc = ContratoService::sacasPagas($codcontrato, $exceto);
                    if ($tetoSc > 0 && $jaSc + (float) $this->input('sacas') > $tetoSc + 1e-6) {
                        $saldoSc = max(0, $tetoSc - $jaSc);
                        $validator->errors()->add(
                            'sacas',
                            'Excede as sacas fixadas (saldo ' . number_format($saldoSc, 0, ',', '.') . ' sc).',
                        );
                    }
                }
            }

            // Recebimento "em conta" exige portador (barter liquida em insumos, isento).
            if (
                $this->filled('datarecebido')
                && $this->input('forma') !== 'BARTER'
                && !$this->filled('codportador')
            ) {
                $validator->errors()->add('codportador', 'Informe o portador onde o recebimento foi realizado.');
            }
        });
    }
}
