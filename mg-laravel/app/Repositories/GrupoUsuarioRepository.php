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
            $usuarios[] = [
                'codusuario' => $usuario->Usuario->codusuario,
                'usuario' => $usuario->Usuario->usuario,
                'filial' => [
                    'codfilial' => $usuario->Usuario->Filial->codfilial,
                    'filial' => $usuario->Usuario->Filial->filial
                ]
            ];
        }

        $permissoes = [];
        foreach ($model->GrupoUsuarioPermissaoS as $permissao) {
            $permissoes[] = [
                'codpermissao' => $permissao->Permissao->codpermissao,
                'permissao' => $permissao->Permissao->permissao
            ];
        }

        $details['Usuarios'] = $usuarios;
        $details['Permissoes'] = $permissoes;

        return $details;
    }

}
