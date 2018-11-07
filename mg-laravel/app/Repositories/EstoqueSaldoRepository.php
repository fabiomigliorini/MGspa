<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\EstoqueSaldo;

class EstoqueSaldoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\EstoqueSaldo';

    public static function validationRules ($model = null)
    {
        $rules = [
            'fiscal' => [
                'boolean',
                'required',
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
            'codestoquelocalprodutovariacao' => [
                'numeric',
                'required',
            ],
            'ultimaconferencia' => [
                'date',
                'nullable',
            ],
            'dataentrada' => [
                'date',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'fiscal.boolean' => 'O campo "fiscal" deve ser um verdadeiro/falso (booleano)!',
            'fiscal.required' => 'O campo "fiscal" deve ser preenchido!',
            'saldoquantidade.numeric' => 'O campo "saldoquantidade" deve ser um número!',
            'saldovalor.numeric' => 'O campo "saldovalor" deve ser um número!',
            'customedio.numeric' => 'O campo "customedio" deve ser um número!',
            'codestoquelocalprodutovariacao.numeric' => 'O campo "codestoquelocalprodutovariacao" deve ser um número!',
            'codestoquelocalprodutovariacao.required' => 'O campo "codestoquelocalprodutovariacao" deve ser preenchido!',
            'ultimaconferencia.date' => 'O campo "ultimaconferencia" deve ser uma data!',
            'dataentrada.date' => 'O campo "dataentrada" deve ser uma data!',
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

    public static function buscaOuCria($codestoquelocal, $codprodutovariacao, $fiscal)
    {
        $elpv = EstoqueLocalProdutoVariacaoRepository::buscaOuCria ($codestoquelocal, $codprodutovariacao);
        if ($model = EstoqueSaldo::where('codestoquelocalprodutovariacao', $elpv->codestoquelocalprodutovariacao)->where('fiscal', $fiscal)->first()) {
            return $model;
        }
        $model = static::new([
            'codestoquelocalprodutovariacao' => $elpv->codestoquelocalprodutovariacao,
            'fiscal' => $fiscal,
        ]);
        return static::save($model);
    }

}
