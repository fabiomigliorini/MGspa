<?php

namespace App\Repositories;

use Validator;
use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\Rule;

use App\Models\Usuario;

/**
 * Description of UsuarioRepository
 *
 */
class UsuarioRepository extends MGRepositoryStatic
{
    public static $modelClass = '\\App\\Models\\Usuario';


    public static function validationRules ($model = null)
    {
        Validator::extendImplicit('senhaantiga', function ($attribute, $value, $parameters) {
            return Hash::check($value, $parameters[0]);
        });

        $rules = [
            'usuario' => [
                'required',
                Rule::unique('tblusuario')->ignore($model->codusuario, 'codusuario'),
                'min:2',
            ],
            'senha' => [
                'required_without:codusuario',
                //'confirmed',
                'min:6'
            ],
            // 'senha_antiga' => [
            //     'senhaantiga:' . \Auth::user()->senha,
            //     'required_with:senha',
            // ],
            // 'senha_confirmation' => [
            //     'min:6'
            // ],
            'impressoramatricial' => [
                'required'
            ],
            'impressoratermica' => [
                'required'
            ]
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'usuario.required' => 'O campo "Usuario" deve ser preenchido!',
            'usuario.unique' => 'Este Usuario já esta cadastrado',
            'usuario.min' => 'O campo Usuario deve ter no mínimo 2 caracteres.',

            'senha.min' => 'O campo Senha deve ter no mínimo 6 caracteres.',
            'senha.required' => 'O campo "Senha" deve ser preenchido!',
            'senha.required_without' => 'O campo "Senha" deve ser preenchido!',

            'impressoratermica.required' => 'O campo "Impressora Termica" deve ser preenchido!',
            'impressoramatricial.required' => 'O campo "Impressora Matricial" deve ser preenchido!',

            'senha_antiga.senhaantiga' => 'Senha antiga não confere',
            //'senha_antiga.senhaantiga' => 'Senha antiga não confere'
        ];

        return $messages;
    }

    public static function details($model)
    {
        $details = $model->getAttributes();
        $details['pessoa'] = [
            'codpessoa' => $model->Pessoa->codpessoa ?? null,
            'pessoa' => $model->Pessoa->pessoa ?? null
        ];

        $details['filial'] = [
            'codfilial' => $model->Filial->codfilial,
            'filial' => $model->Filial->filial
        ];

        $grupos = [];
        $permissoes_array = [];
        $permissoes = [];

        foreach ($model->GrupoUsuarioUsuarioS as $grupo) {

            $grupos[$grupo->GrupoUsuario->grupousuario]['grupousuario'] = $grupo->GrupoUsuario->grupousuario;

            if (!isset($grupos[$grupo->GrupoUsuario->grupousuario]['filiais'])) {
                $grupos[$grupo->GrupoUsuario->grupousuario]['filiais'] = [];
            }

            array_push($grupos[$grupo->GrupoUsuario->grupousuario]['filiais'], $grupo->Filial->filial);

            foreach ($grupo->GrupoUsuario->GrupoUsuarioPermissaoS as $permissao) {
                $permissoes_array[] = $permissao->Permissao->permissao;
            }
        }

        foreach ($permissoes_array as $permissao) {
            $key = explode('.', $permissao);
            if (!isset($permissoes[$key[0]])) {
                $permissoes[$key[0]] = array();
            }
            $permissoes[$key[0]][] = $permissao;
        }

        $details['grupos'] = $grupos;
        $details['permissoes'] = $permissoes;
        $details['imagem'] = $model->Imagem->url ?? false;
        return $details;
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = app(static::$modelClass)::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['usuario'])) {
            $qry->palavras('usuario', $filter['usuario']);
        }

        if (!empty($filter['grupo'])) {
            $qry->whereIn("codusuario", function ($qry2) use ($filter) {
                $qry2->select('codusuario')
                    ->from('tblgrupousuariousuario')
                    ->where('codgrupousuario', $filter['grupo']);
            });
        }

        $qry = static::querySort($qry, $sort);
        $qry = static::queryFields($qry, $fields);
        return $qry;
    }

    public static function create($model = null, array $data = null)
    {
        if (empty($model)) {
            $model = static::new();
        }

        if (!empty($data)) {
            $model = static::fill($model, $data);
        }

        $model->senha = bcrypt($model->senha);

        if (!$model->save()) {
            return false;
        }

        return $model;
    }

    public static function update($model, array $data = null)
    {
        if (!empty($data)) {
            static::fill($model, $data);
        }

        $model->senha = bcrypt($model->senha);

        if (!$model->save()) {
            return false;
        }

        return $model;
    }

    public static function grupos($model)
    {
        $grupos_usuario = [];
        foreach ($model->GrupoUsuarioUsuarioS as $guu) {
            $grupos_usuario[$guu->codgrupousuario][$guu->codfilial] = $guu->codgrupousuariousuario;
        }
        return $grupos_usuario;
    }

    public static function adicionaGrupo($model, $codfilial, $codgrupousuario)
    {
        if (!$model->GrupoUsuarioUsuarioS()->where('codgrupousuario', $codgrupousuario)->where('codfilial', $codfilial)->first()) {
            if (!$grupo_usuario = GrupoUsuarioUsuarioRepository::create(null, [
                'codusuario' => $model->codusuario,
                'codgrupousuario' => $codgrupousuario,
                'codfilial'=> $codfilial
            ])) {
                return false;
            }
        }
        return $grupo_usuario;
    }

    public static function removeGrupo($model, $codfilial, $codgrupousuario)
    {
        if ($model->GrupoUsuarioUsuarioS()->where('codgrupousuario', $codgrupousuario)->where('codfilial', $codfilial)->delete()) {
            return true;
        }

        return false;
    }

}
