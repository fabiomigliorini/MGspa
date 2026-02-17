<?php

namespace App\Http\Requests\Mg\Meta;

use Illuminate\Foundation\Http\FormRequest;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;

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
            'percentualcomissaovendedor' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaovendedormeta' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaoxerox' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaosubgerentemeta' => ['nullable', 'numeric', 'min:0'],
            'premioprimeirovendedorfilial' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string'],

            // Unidades de negocio
            'unidades' => ['sometimes', 'array'],
            'unidades.*.codunidadenegocio' => ['required_with:unidades', 'integer', 'exists:tblunidadenegocio,codunidadenegocio'],
            'unidades.*.valormeta' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetacaixa' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetavendedor' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetaxerox' => ['nullable', 'numeric', 'min:0'],

            // Pessoas por unidade
            'unidades.*.pessoas' => ['sometimes', 'array'],
            'unidades.*.pessoas.*.codpessoa' => ['required', 'integer', 'exists:tblpessoa,codpessoa'],
            'unidades.*.pessoas.*.percentualvenda' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualcaixa' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualsubgerente' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualxerox' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.valorfixo' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.descricaovalorfixo' => ['nullable', 'string'],
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
        ];
    }
}
