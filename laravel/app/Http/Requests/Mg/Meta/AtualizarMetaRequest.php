<?php

namespace App\Http\Requests\Mg\Meta;

use Illuminate\Foundation\Http\FormRequest;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;

class AtualizarMetaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'periodoinicial' => ['sometimes', 'date'],
            'periodofinal' => [
                'sometimes',
                'date',
                'after_or_equal:periodoinicial',
            ],
            'percentualcomissaovendedor' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaovendedormeta' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaoxerox' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaosubgerentemeta' => ['nullable', 'numeric', 'min:0'],
            'premioprimeirovendedorfilial' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string'],

            // Unidades de negocio
            'unidades' => ['sometimes', 'array'],
            'unidades.*.codmetaunidadenegocio' => ['nullable', 'integer'],
            'unidades.*.codunidadenegocio' => ['required_with:unidades', 'integer', 'exists:tblunidadenegocio,codunidadenegocio'],
            'unidades.*.valormeta' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetacaixa' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetavendedor' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.valormetaxerox' => ['nullable', 'numeric', 'min:0'],
            'unidades.*._destroy' => ['sometimes', 'boolean'],

            // Pessoas por unidade
            'unidades.*.pessoas' => ['sometimes', 'array'],
            'unidades.*.pessoas.*.codmetaunidadenegociopessoa' => ['nullable', 'integer'],
            'unidades.*.pessoas.*.codpessoa' => ['required', 'integer', 'exists:tblpessoa,codpessoa'],
            'unidades.*.pessoas.*.percentualvenda' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualcaixa' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualsubgerente' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.percentualxerox' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.valorfixo' => ['nullable', 'numeric', 'min:0'],
            'unidades.*.pessoas.*.descricaovalorfixo' => ['nullable', 'string'],
            'unidades.*.pessoas.*._destroy' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $codmeta = $this->route('codmeta');
            $meta = Meta::find($codmeta);

            if (!$meta) {
                return;
            }

            if ($meta->status === MetaService::META_STATUS_FECHADA) {
                $validator->errors()->add('status', "Meta #{$codmeta} esta fechada e nao pode ser alterada.");
            }

            if ($meta->processando) {
                $validator->errors()->add('processando', "Meta #{$codmeta} esta sendo processada. Aguarde.");
            }
        });
    }

    public function messages()
    {
        return [
            'periodofinal.after_or_equal' => 'O periodo final deve ser igual ou posterior ao periodo inicial.',
            'unidades.*.codunidadenegocio.exists' => 'Unidade de negocio nao encontrada.',
            'unidades.*.pessoas.*.codpessoa.exists' => 'Pessoa nao encontrada.',
        ];
    }
}
