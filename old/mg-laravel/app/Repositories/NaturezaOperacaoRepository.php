<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Description of NaturezaOperacaoRepository
 *
 * @property Validator $validator
 * @property NaturezaOperacao $model
 */
class NaturezaOperacaoRepository extends MGRepositoryStatic
{
    public static $modelClass = '\\App\\Models\\NaturezaOperacao';

    public static function validationRules ($model = null)
    {
        $rules = [
            'naturezaoperacao' => [
                'max:50',
                'min:10',
                'required',
                Rule::unique('tblnaturezaoperacao')->ignore($model->codnaturezaoperacao),
            ],
            'codoperacao' => [
                'numeric',
                'required',
            ],
            'emitida' => [
                'boolean',
                'required',
            ],
            'observacoesnf' => [
                'max:500',
                'nullable',
            ],
            'mensagemprocom' => [
                'max:500',
                'nullable',
            ],
            'codnaturezaoperacaodevolucao' => [
                'numeric',
                'nullable',
            ],
            'codtipotitulo' => [
                'numeric',
                'required',
            ],
            'codcontacontabil' => [
                'numeric',
                'required',
            ],
            'finnfe' => [
                'numeric',
                'required',
            ],
            'ibpt' => [
                'numeric',
                'required',
            ],
            'estoque' => [
                'boolean',
                'required',
            ],
            'financeiro' => [
                'boolean',
                'required',
            ],
            'compra' => [
                'boolean',
                'required',
            ],
            'venda' => [
                'boolean',
                'required',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'naturezaoperacao.max' => 'O campo "Natureza de Operação" não pode conter mais que 50 caracteres!',
            'naturezaoperacao.min' => 'O campo "Natureza de Operação" não pode conter menos que 10 caracteres!',
            'naturezaoperacao.required' => 'O campo "Natureza de Operação" deve ser preenchido!',
            'produto.unique' => 'Já existe uma Natureza de Operação com esta descrição!',
        ];
        return $messages;
    }

}
