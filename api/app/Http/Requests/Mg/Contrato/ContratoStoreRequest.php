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
        // FIXO trava o preço no próprio contrato (vira fixação-espelho), então
        // o preço é obrigatório e > 0; FIXAR/BARTER fixam à mão depois (preço
        // aqui é só referência, pode faltar).
        $preco = $this->input('tipo') === 'FIXO'
            ? ['required', 'numeric', 'gt:0']
            : ['nullable', 'numeric', 'gte:0'];

        return [
            'contrato' => ['required', 'min:1'],
            'codpessoa' => ['required', 'exists:tblpessoa,codpessoa'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'codsafra' => ['nullable', 'exists:tblsafra,codsafra'],
            'tipo' => ['required', Rule::in(['FIXO', 'FIXAR', 'BARTER'])],
            // O contrato pode nascer como rascunho (só identificação: nº, produtor,
            // data, comprador) e ter a quantidade definida depois na tela do contrato.
            'quantidade' => ['nullable', 'numeric', 'gte:0'],
            'semlimite' => ['nullable', 'boolean'],
            'preco' => $preco,
            'moeda' => ['nullable', Rule::in(['BRL', 'USD'])],
            'dataembarque' => ['nullable', 'date'],
            'localentrega' => ['nullable', 'string'],
            'observacao' => ['nullable', 'string'],
            'observacaonf' => ['nullable', 'string'],
            'codnaturezaoperacao' => ['nullable', 'exists:tblnaturezaoperacao,codnaturezaoperacao'],
            'codpessoanf' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'isentofethab' => ['nullable', 'boolean'],
            'codfilial' => ['nullable', 'exists:tblfilial,codfilial'],
            'datacontrato' => ['nullable', 'date'],
            'embarqueinicio' => ['nullable', 'date'],
            'embarquefim' => ['nullable', 'date'],
            'codportador' => ['nullable', 'exists:tblportador,codportador'],
            'codpessoacorretora' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'comissaotipo' => ['nullable', Rule::in(['PERCENTUAL', 'SACA', 'TOTAL'])],
            'comissaovalor' => ['nullable', 'numeric', 'gte:0'],
            'comissaototal' => ['nullable', 'numeric', 'gte:0'],
            'viacooperativa' => ['nullable', 'boolean'],
            'codpessoacooperativa' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'numerocomprador' => ['nullable', 'max:30'],
            'numerocorretora' => ['nullable', 'max:30'],
            'numerocooperativa' => ['nullable', 'max:30'],
        ];
    }
}
