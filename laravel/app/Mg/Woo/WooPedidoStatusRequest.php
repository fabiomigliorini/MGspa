<?php

namespace Mg\Woo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Adicionar o Rule para regras condicionais

class WooPedidoStatusRequest extends FormRequest
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
            'shipped', // A caminho
        ];

        return [

            // Filtro 'status' (pode ser uma string ou um array de strings)
            'status' => [
                'required',
                'string',
                Rule::in($statusValidos),
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
            // 'status.in' => 'Status inválido!',
        ];
    }

}
