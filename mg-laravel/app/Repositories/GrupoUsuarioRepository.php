<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\GrupoUsuario;

/**
 * Description of GrupoUsuarioRepository
 *
 */
class GrupoUsuarioRepository extends MGRepositoryStatic {

    public static $modelClass = 'App\\Models\\GrupoUsuario';

    public static function validationRules ($model = null)
    {
        $rules = [
            'grupousuario' => [
                'required',
                Rule::unique('tblgrupousuario')->ignore($model->codgrupousuario, 'codgrupousuario')
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'grupousuario.required' => 'O campo Grupo de Usuario não pode ser vazio',
            'grupousuario.unique' => 'Este Grupo de Usuario já esta cadastrado',
        ];

        return $messages;
    }
}
