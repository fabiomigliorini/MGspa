<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Permissao;

/**
 * Description of PermissaoRepository
 *
 */
class PermissaoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'Permissao';

    public static function validate($model = null, array $data = null, &$errors)
    {
        if (empty($data)) {
            if (empty($model)) {
                return false;
            }
            $data = $model->getAttributes();
        }

        $id = $data['codpermissao']??$model->codpermissao??null;

        $validator = Validator::make($data, [
            'permissao' => [
                'required',
                Rule::unique('tblpermissao')->ignore($id, 'codpermissao')
            ],
        ], [
            'permissao.required' => 'O campo Permissao não pode ser vazio',
            'permissao.unique' => 'Esta Permissao já esta cadastrada',
        ]);

        if (!$validator->passes()) {
            $errors = $validator->errors()->all();
            return false;
        }

        return true;
    }

    public static function used($model)
    {
        if ($model->GrupoUsuario->count() > 0) {
            return 'Permissão já anexada para um Grupo de Usuários!';
        }
        return false;
    }
}
