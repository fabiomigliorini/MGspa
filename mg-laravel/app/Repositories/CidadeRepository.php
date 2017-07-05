<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Cidade;

class CidadeRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\Cidade';

    public static function validationRules ($model = null)
    {
        $rules = [
            'codestado' => [
                'numeric',
                'nullable',
            ],
            'cidade' => [
                'max:50',
                'nullable',
            ],
            'sigla' => [
                'max:3',
                'nullable',
            ],
            'codigooficial' => [
                'numeric',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'codestado.numeric' => 'O campo "codestado" deve ser um número!',
            'cidade.max' => 'O campo "cidade" não pode conter mais que 50 caracteres!',
            'sigla.max' => 'O campo "sigla" não pode conter mais que 3 caracteres!',
            'codigooficial.numeric' => 'O campo "codigooficial" deve ser um número!',
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

}
