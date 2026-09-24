<?php

namespace App\Http\Requests\Mg\Vale;

use Illuminate\Foundation\Http\FormRequest;

class ValeModeloStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'modelo' => ['required', 'max:100'],
            // opcional: sem escola o vale e ao portador (decisao 13)
            'codpessoafavorecido' => ['nullable', 'integer', 'exists:tblpessoa,codpessoa'],
            'observacoes' => ['nullable', 'max:200'],
            // valorprodutos e valortotal sao calculados; so o avulso e digitado
            'valoravulso' => ['nullable', 'numeric', 'gte:0'],
            'itens' => ['array'],
            'itens.*.codvalemodeloprodutobarra' => ['nullable', 'integer'],
            'itens.*.codprodutobarra' => ['required', 'integer', 'exists:tblprodutobarra,codprodutobarra'],
            'itens.*.quantidade' => ['required', 'numeric', 'gt:0'],
            'itens.*.valorunitario' => ['required', 'numeric', 'gte:0'],
        ];
    }

    public function attributes()
    {
        return [
            'modelo' => 'descrição',
            'codpessoafavorecido' => 'favorecido',
            'valoravulso' => 'valor avulso',
            'itens.*.codprodutobarra' => 'produto',
            'itens.*.quantidade' => 'quantidade',
            'itens.*.valorunitario' => 'valor unitário',
        ];
    }
}
