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
                Rule::unique('tblgrupousuario')->ignore($model->codgrupousuario, 'codgrupousuario'),
                'min:3',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'grupousuario.required' => 'O campo Grupo de Usuario não pode ser vazio',
            'grupousuario.unique' => 'Este Grupo de Usuario já esta cadastrado',
            'grupousuario.min' => 'O campo Grupo de Usuario deve ter no mínimo 3 caracteres.',
        ];

        return $messages;
    }

    public static function details($model)
    {
        $details = $model->getAttributes();

        $usuarios = [];
        foreach ($model->GrupoUsuarioUsuarioS as $usuario) {

            foreach ($usuario->Usuario->GrupoUsuarioUsuarioS as $grupo) {
                $grupos[$grupo->GrupoUsuario->codgrupousuario] = $grupo->GrupoUsuario->grupousuario;
            }

            $usuarios[$usuario['codusuario']] = [
                'codusuario' => $usuario->Usuario->codusuario,
                'usuario' => $usuario->Usuario->usuario,
                'grupos' => $grupos
            ];
        }

        $details['Usuarios'] = $usuarios;
        $details['usuario'] = [
            'usuariocriacao' => $model->UsuarioCriacao->usuario ?? false,
            'usuarioalteracao' => $model->UsuarioAlteracao->usuario ?? false
        ];

        return $details;
    }

}
