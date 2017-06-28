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

    public static function validate($model = null, &$errors = null, $throwsException = true)
    {
        $data = $model->getAttributes();

        $rules = [
            'grupousuario' => [
                'required',
                Rule::unique('tblgrupousuario')->ignore($model->codgrupousuario, 'codgrupousuario')
            ],
        ];

        $messages = [
            'grupousuario.required' => 'O campo Grupo de Usuario não pode ser vazio',
            'grupousuario.unique' => 'Este Grupo de Usuario já esta cadastrado',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($throwsException) {
            $validator->validate();
            return true;
        }

        if (!$validator->passes()) {
            $errors = $validator->errors();
            return false;
        }

        return true;
    }

    public static function used($model) {

        if ($model->GrupoUsuarioPermissaoS->count() > 0) {
            return 'Grupo de usuário sendo utilizada em Permissões!';
        }
        if ($model->GrupoUsuarioUsuarioS->count() > 0) {
            return 'Grupo de usuário sendo utilizada em Usuarios!';
        }
        return false;
    }
}
