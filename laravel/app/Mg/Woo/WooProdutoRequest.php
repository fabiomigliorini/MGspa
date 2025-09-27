<?php

namespace Mg\Woo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Adicionar o Rule para regras condicionais

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
        // 1. Pega o valor do parâmetro da rota.
        // Assumindo que sua rota de PUT é 'produto/{codwooproduto}', o nome do parâmetro é 'codwooproduto'.
        $primaryKeyValue = $this->route('codwooproduto');

        // 2. Monta a regra de unicidade para o campo 'id' (ID do Woo).
        $idUniqueRule = Rule::unique('tblwooproduto', 'id');
        $idVariationUniqueRule = Rule::unique('tblwooproduto', 'idvariation');

        // Verifica se estamos em um update (o parâmetro da rota existe)
        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $idUniqueRule->ignore($primaryKeyValue, 'codwooproduto');
            $idVariationUniqueRule->ignore($primaryKeyValue, 'codwooproduto');
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
                $idUniqueRule, // Usa a regra dinâmica (com exceção no PUT)
            ],

            'idvariation' => [
                'nullable',
                'integer',
                // Aplica a regra de unicidade se o campo for preenchido
                'exclude_if:idvariation,null',
                $idVariationUniqueRule,
            ],

            'integracao' => [
                'required',
                'string',
                'max:1'
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
            'id.unique' => 'O ID do produto Woo já existe.'
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

        // 3. Retorna APENAS os campos permitidos que estavam nos dados validados.
        return array_intersect_key($validatedData, array_flip($allowedFields));
    }
}
