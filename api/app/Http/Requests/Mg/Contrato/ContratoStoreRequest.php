<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Safra\Safra;

class ContratoStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    // O contrato vive dentro de uma safra, e a safra sempre tem a cultura. Se o
    // cliente mandou a safra mas não a cultura, deriva a cultura da safra — não
    // existe select de cultura no form (é herdada do contexto da safra).
    protected function prepareForValidation()
    {
        if (!$this->filled('codcultura') && $this->filled('codsafra')) {
            $codcultura = Safra::whereKey($this->input('codsafra'))->value('codcultura');
            if ($codcultura) {
                $this->merge(['codcultura' => $codcultura]);
            }
        }
    }

    public function rules()
    {
        return [
            'contrato' => ['required', 'min:1'],
            'codpessoa' => ['required', 'exists:tblpessoa,codpessoa'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'codsafra' => ['nullable', 'exists:tblsafra,codsafra'],
            'operacao' => ['nullable', Rule::in(['COMPRA', 'VENDA'])],
            // Barter = settlement em insumos (troca por insumos). Flag no contrato.
            'barter' => ['nullable', 'boolean'],
            // quantidade NULL = volume em aberto (leva o saldo do silo). O contrato
            // tambem pode nascer rascunho (só identificação) e ter a quantidade
            // definida depois. Quando informada, deve ser > 0 (0 não distingue de
            // "em aberto"). Precificação (preço/moeda/isenção) vive na fixação;
            // NF (natureza/pessoa/observação) no plano de notas (tblcontratonota).
            'quantidade' => ['nullable', 'numeric', 'gt:0'],
            'dataembarque' => ['nullable', 'date'],
            'localentrega' => ['nullable', 'string'],
            'observacao' => ['nullable', 'string'],
            'codfilial' => ['nullable', 'exists:tblfilial,codfilial'],
            // Janela de embarque coerente: fim >= início (só valida quando ambas
            // preenchidas). datacontrato fica livre — lançamento retroativo de
            // contrato histórico pode ter assinatura fora da janela.
            'datacontrato' => ['nullable', 'date'],
            'embarqueinicio' => ['nullable', 'date'],
            'embarquefim' => ['nullable', 'date', 'after_or_equal:embarqueinicio'],
            'codportador' => ['nullable', 'exists:tblportador,codportador'],
            'codpessoacorretora' => ['nullable', 'exists:tblpessoa,codpessoa'],
            // Comissão só faz sentido com corretora: tipo/valor exigidos quando há corretora.
            'comissaotipo' => ['nullable', 'required_with:codpessoacorretora', Rule::in(['PERCENTUAL', 'SACA', 'TOTAL'])],
            'comissaovalor' => ['nullable', 'required_with:codpessoacorretora', 'numeric', 'gte:0'],
            'comissaototal' => ['nullable', 'numeric', 'gte:0'],
            'codpessoacooperativa' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'codtabelaclassificacao' => ['nullable', 'exists:tbltabelaclassificacao,codtabelaclassificacao'],
            'numerocontraparte' => ['nullable', 'max:30'],
            'numerocorretora' => ['nullable', 'max:30'],
            'numerocooperativa' => ['nullable', 'max:30'],
        ];
    }
}
