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
            $fil[] = $usuario->Filial->filial;
            $usuarios[$usuario['codusuario']] = [
                'codusuario' => $usuario->Usuario->codusuario,
                'usuario' => $usuario->Usuario->usuario,
                'filiais' => $fil
            ];
        }

        $permissoes = [];
        foreach ($model->GrupoUsuarioPermissaoS as $permissao) {
            $permissoes[] = [
                'codpermissao' => $permissao->Permissao->codpermissao,
                'permissao' => $permissao->Permissao->permissao
            ];
        }

        $routes = \Route::getRoutes();
        foreach ($routes as $rota) {
            $nome = $rota->getName();
            if (!empty($nome)) {
                $rotas[] = $nome;
            }
        }
        $rotas = array_unique($rotas);

        $permissoes_collapse = [];
        foreach ($permissoes as $permissao) {
            if (in_array($permissao['permissao'], $rotas)) {
                $key = explode('.', $permissao['permissao']);
                if(!isset($permissoes_collapse[$key[0]])){
                    $permissoes_collapse[$key[0]] = array();
                }
                $permissoes_collapse[$key[0]][] = $permissao;
            } else {
                $permissoes_collapse['INATIVO'][] = $permissao;
            }
        }

        $details['Usuarios'] = $usuarios;
        $details['Permissoes'] = $permissoes_collapse;
        $details['usuario'] = [
            'usuariocriacao' => $model->UsuarioCriacao->usuario ?? false,
            'usuarioalteracao' => $model->UsuarioAlteracao->usuario ?? false
        ];

        return $details;
    }

}
