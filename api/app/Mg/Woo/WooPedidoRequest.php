<?php

namespace Mg\Woo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Adicionar o Rule para regras condicionais


class WooPedidoRequest extends FormRequest
{

    public function rules()
    {
        // Lista de status válidos (ajuste conforme os status reais do WooCommerce que você usa)
        $statusValidos = [
            'pending', // Pendente
            'processing', // Processando
            'on-hold', // Em espera
            'completed', // Concluído
            'cancelled', // Cancelado
            'refunded', // Reembolsado
            'failed', // Falhou
            // Adicione outros status personalizados aqui
        ];

        return [
            // Filtro 'id'
            'id' => [
                'nullable',
                'integer',
                'min:1'
            ],

            // Filtro 'status' (pode ser uma string ou um array de strings)
            'status' => [
                'nullable',
                'array'
            ],
            'status.*' => [
                'string',
                Rule::in($statusValidos),
            ],

            // Filtro 'nome'
            'nome' => [
                'nullable',
                'string',
                'max:255'
            ],

            // Filtros de Data ('criacaowoo_de' e 'criacaowoo_ate')
            'criacaowoo_de' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'criacaowoo_ate' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:criacaowoo_de' // Garante que a data 'ate' seja posterior ou igual à data 'de'.
            ],

            // Filtros de Valor Total ('valortotal_de' e 'valortotal_ate')
            'valortotal_de' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'valortotal_ate' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:valortotal_de' // Garante que o valor 'ate' seja maior ou igual ao valor 'de'.
            ],
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
            'date_format' => 'O campo :attribute deve estar no formato AAAA-MM-DD.',
            'after_or_equal' => 'O campo :attribute deve ser uma data ou valor posterior ou igual a :value.',
            'in' => 'O Status selecionado é inválido.',
            'numeric' => 'O campo :attribute deve ser um número.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'status.array' => 'O campo :attribute deve ser um array (listagem).',
            // Mensagens específicas se necessário, ex:
            'valortotal_de.min' => 'O valor total inicial não pode ser negativo.'
        ];
    }

}
