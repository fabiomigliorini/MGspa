<?php

namespace App\Http\Requests\Mg\Vale;

use Illuminate\Foundation\Http\FormRequest;

/** Filtros da consulta de vales emitidos (listagem e relatorio). */
class ValeEmitidoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'busca' => ['nullable', 'string', 'max:100'],
            'codvalemodelo' => ['nullable', 'integer'],
            'codpessoafavorecido' => ['nullable', 'integer'],
            'codnegocio' => ['nullable', 'integer'],
            'valorde' => ['nullable', 'numeric'],
            'valorate' => ['nullable', 'numeric'],
            'situacao' => ['nullable', 'in:ativo,cancelado,todos'],
            'comsaldo' => ['nullable', 'boolean'],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages()
    {
        return [
            'ate.after_or_equal' => 'A data "Até" deve ser igual ou posterior à data "De".',
        ];
    }
}
