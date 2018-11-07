<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Pais;

class PaisRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\Pais';

    public static function validationRules ($model = null)
    {
        $rules = [
            'pais' => [
                'max:50',
                'required',
            ],
            'sigla' => [
                'max:2',
                'required',
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
            'pais.max' => 'O campo "pais" não pode conter mais que 50 caracteres!',
            'pais.required' => 'O campo "pais" deve ser preenchido!',
            'sigla.max' => 'O campo "sigla" não pode conter mais que 2 caracteres!',
            'sigla.required' => 'O campo "sigla" deve ser preenchido!',
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
