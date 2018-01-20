<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\EstoqueMes;

class EstoqueMesRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\EstoqueMes';

    public static function validationRules ($model = null)
    {
        $rules = [
            'codestoquesaldo' => [
                'numeric',
                'required',
            ],
            'mes' => [
                'date',
                'required',
            ],
            'inicialquantidade' => [
                'numeric',
                'nullable',
            ],
            'inicialvalor' => [
                'numeric',
                'nullable',
            ],
            'entradaquantidade' => [
                'numeric',
                'nullable',
            ],
            'entradavalor' => [
                'numeric',
                'nullable',
            ],
            'saidaquantidade' => [
                'numeric',
                'nullable',
            ],
            'saidavalor' => [
                'numeric',
                'nullable',
            ],
            'saldoquantidade' => [
                'numeric',
                'nullable',
            ],
            'saldovalor' => [
                'numeric',
                'nullable',
            ],
            'customedio' => [
                'numeric',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'codestoquesaldo.numeric' => 'O campo "codestoquesaldo" deve ser um número!',
            'codestoquesaldo.required' => 'O campo "codestoquesaldo" deve ser preenchido!',
            'mes.date' => 'O campo "mes" deve ser uma data!',
            'mes.required' => 'O campo "mes" deve ser preenchido!',
            'inicialquantidade.numeric' => 'O campo "inicialquantidade" deve ser um número!',
            'inicialvalor.numeric' => 'O campo "inicialvalor" deve ser um número!',
            'entradaquantidade.numeric' => 'O campo "entradaquantidade" deve ser um número!',
            'entradavalor.numeric' => 'O campo "entradavalor" deve ser um número!',
            'saidaquantidade.numeric' => 'O campo "saidaquantidade" deve ser um número!',
            'saidavalor.numeric' => 'O campo "saidavalor" deve ser um número!',
            'saldoquantidade.numeric' => 'O campo "saldoquantidade" deve ser um número!',
            'saldovalor.numeric' => 'O campo "saldovalor" deve ser um número!',
            'customedio.numeric' => 'O campo "customedio" deve ser um número!',
        ];

        return $messages;
    }

    public static function details($model)
    {
        return parent::details ($model);
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        return parent::query ($filter, $sort, $fields);
    }

    public static function buscaOuCria($codestoquelocal, $codprodutovariacao, $fiscal, $data)
    {
        // Inicio do Mes
        $mes = clone $data;
        $mes = $mes->startOfMonth($mes);

        // Se for fiscal cria somente um mês por ano, dezembro, até 2016
        if ($fiscal && $mes->year <= 2016) {
            $mes->month = 12;
        }

        $es = EstoqueSaldoRepository::buscaOuCria($codestoquelocal, $codprodutovariacao, $fiscal);
        if ($model = EstoqueMes::where('codestoquesaldo', $es->codestoquesaldo)->where('mes', $mes)->first()) {
            return $model;
        }
        $model = static::new([
            'codestoquesaldo' => $es->codestoquesaldo,
            'mes' => $mes,
        ]);
        return static::save($model);
    }

}
