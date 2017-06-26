<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\GrupoUsuario;

/**
 * Description of GrupoUsuarioRepository
 *
 * @property Validator $validator
 * @property GrupoUsuario $model
 */
class GrupoUsuarioRepository extends MGRepositoryStatic {

    public static $modelClass = 'GrupoUsuario';

    public static function validate($model = null, array $data = null, &$errors)
    {
        if (empty($data)) {
            if (empty($model)) {
                return false;
            }
            $data = $model->getAttributes();
        }

        $id = $data['codgrupousuario']??$model->codgrupousuario??null;

        $validator = Validator::make($data, [
            'grupousuario' => [
                'required',
                Rule::unique('tblgrupousuario')->ignore($id, 'codgrupousuario')
            ],
        ], [
            'grupousuario.required' => 'O campo Grupo de Usuario não pode ser vazio',
            'grupousuario.unique' => 'Este Grupo de Usuario já esta cadastrado',
        ]);

        if (!$validator->passes()) {
            $errors = $validator->errors()->all();
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
