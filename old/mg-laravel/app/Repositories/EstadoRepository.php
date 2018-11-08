<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Estado;

class EstadoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\Estado';

    public static function validationRules ($model = null)
    {
        $rules = [
            'codpais' => [
                'numeric',
                'nullable',
            ],
            'estado' => [
                'max:50',
                'nullable',
            ],
            'sigla' => [
                'max:2',
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
            'codpais.numeric' => 'O campo "codpais" deve ser um número!',
            'estado.max' => 'O campo "estado" não pode conter mais que 50 caracteres!',
            'sigla.max' => 'O campo "sigla" não pode conter mais que 2 caracteres!',
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
