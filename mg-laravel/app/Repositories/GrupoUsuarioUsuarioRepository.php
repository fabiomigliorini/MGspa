<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\GrupoUsuarioUsuario;

class GrupoUsuarioUsuarioRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\GrupoUsuarioUsuario';

    public static function validationRules ($model = null)
    {
        $rules = [
            'codgrupousuario' => [
                'numeric',
                'required',
            ],
            'codusuario' => [
                'numeric',
                'required',
            ],
            'codfilial' => [
                'numeric',
                'required',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'codgrupousuario.numeric' => 'O campo "codgrupousuario" deve ser um número!',
            'codgrupousuario.required' => 'O campo "codgrupousuario" deve ser preenchido!',
            'codusuario.numeric' => 'O campo "codusuario" deve ser um número!',
            'codusuario.required' => 'O campo "codusuario" deve ser preenchido!',
            'codfilial.numeric' => 'O campo "codfilial" deve ser um número!',
            'codfilial.required' => 'O campo "codfilial" deve ser preenchido!',
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
