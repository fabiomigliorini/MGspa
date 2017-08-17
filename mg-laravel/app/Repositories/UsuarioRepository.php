<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
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
        $min = ($model->codusuario) ? 6 : 0;
        $rules = [
            'usuario' => [
                'required',
                Rule::unique('tblusuario')->ignore($model->codusuario, 'codusuario'),
                'min:2',
            ],
            'senha' => [
                'required_if:codusuario,null',
                "min:$min"
            ],
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
            'usuario.required' => 'O campo Usuario não pode ser vazio',
            'usuario.unique' => 'Este Usuario já esta cadastrado',
            'usuario.min' => 'O campo Usuario deve ter no mínimo 2 caracteres.',

            'senha.min' => 'O campo Senha deve ter no mínimo 6 caracteres.',
            'senha.required' => 'O campo Senha não pode ser vazio',
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

        if (empty($model->senha)) {
            unset($model->senha);
        } else {
            $model->senha = bcrypt($model->senha);
        }

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
