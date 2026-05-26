<?php

namespace Mg\Woo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Adicionar o Rule para regras condicionais

use Mg\Produto\ProdutoBarra;

class WooProdutoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Set to true if authorization logic is handled elsewhere (e.g., middleware)
        return true;

        // Example: return \Auth::user()->can('manage-woo-produtos');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $primaryKeyValue = $this->route('codwooproduto');
        $tableName = 'tblwooproduto';

        // 1. REGRAS DE UNICIDADE COMPOSTA
        // A combinação das colunas 'id' E 'idvariation' DEVE ser única.
        $uniqueCompositeRule = Rule::unique($tableName)->where(function ($query) {
            // O valor do campo 'idvariation' (que é 'nullable') deve ser buscado aqui
            $query->where('idvariation', $this->idvariation);
        });

        // 2. TRATAMENTO DO UPDATE (PUT/PATCH)
        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            // O método 'ignore' deve ser aplicado à regra de unicidade composta.
            // Ele vai ignorar o registro atual baseado na chave primária 'codwooproduto'.
            $uniqueCompositeRule->ignore($primaryKeyValue, 'codwooproduto');
        }

        return [
            'codproduto' => [
                'required',
                'integer',
                'exists:tblproduto,codproduto'
            ],

            'codprodutobarraunidade' => [
                'nullable',
                'integer',
                'exists:tblprodutobarraunidade,codprodutobarraunidade'
            ],

            'codprodutovariacao' => [
                'nullable',
                'integer',
                'exists:tblprodutovariacao,codprodutovariacao'
            ],

            'id' => [
                'required',
                'integer',
                $uniqueCompositeRule, // Usa a regra dinâmica (com exceção no PUT)
            ],

            'idvariation' => [
                'nullable',
                'integer',
                // Não precisamos de 'exclude_if:idvariation,null' aqui se a coluna for nullable no DB.
                // A regra de unicidade composta em 'id' já trata a combinação.
            ],

            'integracao' => [
                'required',
                'string',
                'max:1'
            ],

            'barrasunidade' => [
                'nullable',
                'exists:tblprodutobarra,barras'
            ],

            // Campos NULLABLE (mas que você pode querer validar o formato se vierem):
            'margempacote' => ['nullable', 'numeric'],
            'margemunidade' => ['nullable', 'numeric'],
            'quantidadeembalagem' => ['nullable', 'numeric'],
            'quantidadepacote' => ['nullable', 'numeric'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'codproduto.required' => 'O código do produto principal é obrigatório.',
            'id.unique' => 'O ID do produto Woo já existe.',
            'barrasunidade.exists' => 'O código de barras da unidade não foi localizado em nenhum cadastro.'
        ];
    }

    /**
     * Retorna apenas os dados validados e permitidos para a atribuição em massa.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return array
     */
    public function validated($key = null, $default = null)
    {
        // 1. Pega os dados que passaram nas regras de validação.
        $validatedData = parent::validated();

        // 2. Define os campos que são permitidos para fill/update.
        $allowedFields = [
            'codproduto',
            'codprodutobarraunidade',
            'codprodutovariacao',
            'id',
            'idvariation',
            'integracao',
            'margempacote',
            'margemunidade',
            'quantidadeembalagem',
            'quantidadepacote',
        ];

        $ret = array_intersect_key($validatedData, array_flip($allowedFields));
        $ret['codprodutobarraunidade'] = $this->buscarCodProdutoBarra($this->barrasunidade);

        // 3. Retorna APENAS os campos permitidos que estavam nos dados validados.
        return $ret;
    }

    public function buscarCodProdutoBarra($barrasunidade)
    {
        if (empty($barrasunidade)) {
            return null;
        }
        $pb = ProdutoBarra::where('barras', $barrasunidade)->first();
        if (!$pb) {
            throw new \Exception("Código de barras informado não existe!", 1);
        }
        return $pb->codprodutobarra;
    }
}
