<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Mg\Contrato\ContratoFixacao;
use Mg\Contrato\ContratoFixacaoCambio;

class ContratoFixacaoCambioRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'data' => ['required', 'date'],
            // valor travado, na MOEDA da fixação (US$/€…). Cotação = R$/moeda.
            'valor' => ['required', 'numeric', 'gt:0'],
            'cotacao' => ['required', 'numeric', 'gt:0'],
            'observacao' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * Teto: a soma das travas ativas + esta não pode passar do total em moeda da
     * fixação (quantidade × preço). Na edição, ignora a própria trava. Backend é
     * a fonte de verdade — o front só espelha (pré-preenche com o saldo).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $codfixacao = (int) $this->route('codfixacao');
            $fixacao = ContratoFixacao::find($codfixacao);
            if (!$fixacao) {
                return; // 404 tratado no controller
            }
            $totalmoeda = (float) $fixacao->quantidade * (float) $fixacao->preco;

            $codcambio = $this->route('codcambio');
            $exceto = $codcambio !== null ? (int) $codcambio : null;
            $jaTravado = (float) ContratoFixacaoCambio::where('codcontratofixacao', $codfixacao)
                ->whereNull('inativo')
                ->when($exceto, fn ($q) => $q->where('codcontratofixacaocambio', '!=', $exceto))
                ->sum('valor');

            $novo = (float) $this->input('valor');
            if ($jaTravado + $novo > $totalmoeda + 0.005) {
                $saldo = max(0, $totalmoeda - $jaTravado);
                $validator->errors()->add(
                    'valor',
                    'Excede o saldo a travar da fixação (' . number_format($saldo, 2, ',', '.') . ').',
                );
            }
        });
    }
}
