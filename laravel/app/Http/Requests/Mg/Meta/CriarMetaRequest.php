<?php

namespace App\Http\Requests\Mg\Meta;

use Illuminate\Foundation\Http\FormRequest;

class CriarMetaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'periodoinicial' => ['required', 'date'],
            'periodofinal' => [
                'required',
                'date',
                'after_or_equal:periodoinicial',
            ],
            'status' => ['sometimes', 'string', 'in:A,B'],
            'observacoes' => ['nullable', 'string'],

            // Unidades de negocio
            'unidades' => ['sometimes', 'array'],
            'unidades.*.codunidadenegocio' => ['required_with:unidades', 'integer', 'exists:tblunidadenegocio,codunidadenegocio'],
            'unidades.*.valormeta' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetacaixa' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetavendedor' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetaxerox' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.percentualcomissaovendedor' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.percentualcomissaovendedormeta' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.percentualcomissaosubgerente' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.percentualcomissaosubgerentemeta' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.percentualcomissaoxerox' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.premioprimeirovendedor' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.premiosubgerentemeta' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.premiometaxerox' => ['nullable', 'numeric', 'min:0'],

            // Pessoas por unidade
            'unidades.*.pessoas' => ['sometimes', 'array'],
            'unidades.*.pessoas.*.codpessoa' => ['required', 'integer', 'exists:tblpessoa,codpessoa'],
            'unidades.*.pessoas.*.datainicial' => ['required', 'date'],
            'unidades.*.pessoas.*.datafinal' => ['required', 'date', 'after_or_equal:unidades.*.pessoas.*.datainicial'],
            'unidades.*.pessoas.*.percentualvenda' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualcaixa' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualsubgerente' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualxerox' => ['nullable', 'numeric', 'min:0'],

            // Fixos por pessoa
            'unidades.*.pessoas.*.fixos' => ['sometimes', 'array'],
            'unidades.*.pessoas.*.fixos.*.tipo' => ['required', 'string'],
            'unidades.*.pessoas.*.fixos.*.valor' => ['nullable', 'numeric'],
            'unidades.*.pessoas.*.fixos.*.quantidade' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.fixos.*.descricao' => ['nullable', 'string'],
            'unidades.*.pessoas.*.fixos.*.datainicial' => ['nullable', 'date'],
            'unidades.*.pessoas.*.fixos.*.datafinal' => ['nullable', 'date'],
        ];
    }

    public function messages()
    {
        return [
            'periodoinicial.required' => 'O periodo inicial e obrigatorio.',
            'periodofinal.required' => 'O periodo final e obrigatorio.',
            'periodofinal.after_or_equal' => 'O periodo final deve ser igual ou posterior ao periodo inicial.',
            'unidades.*.codunidadenegocio.exists' => 'Unidade de negocio nao encontrada.',
            'unidades.*.pessoas.*.codpessoa.exists' => 'Pessoa nao encontrada.',
            'unidades.*.pessoas.*.datainicial.required' => 'A data inicial da pessoa e obrigatoria.',
            'unidades.*.pessoas.*.datafinal.required' => 'A data final da pessoa e obrigatoria.',
        ];
    }
}
