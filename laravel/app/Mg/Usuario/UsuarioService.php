<?php

namespace Mg\Usuario;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\MgService;

class UsuarioService extends MgService
{
    public static function detalhes($id)
    {

        $model = Usuario::findOrFail($id);
        $model['pessoa'] = [
            'codpessoa' => $model->Pessoa->codpessoa ?? null,
            'pessoa' => $model->Pessoa->pessoa ?? null
        ];

        $model['filial'] = [
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

        $model['grupos'] = $grupos;
        $model['permissoes'] = $permissoes;
        $model['avatar'] = $model->Imagem->url ?? false;

        return $model;
    }

    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = Usuario::query();

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

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }


    public static function buscaGrupoPermissoes()
    {
        $sql = 'select guu.codgrupousuariousuario, gu.grupousuario, guu.codfilial
        from tblgrupousuariousuario guu
        inner join tblgrupousuario gu on (gu.codgrupousuario = guu.codgrupousuario)
        where guu.codusuario = :codusuario';

        $params['codusuario'] = Auth::user()->codusuario;

        $ret = DB::select($sql, $params);
        $result = null;

        foreach ($ret as $key => $value) {
            $result[] = [
                'grupo' => $value->grupousuario,
                'codfilial' => $value->codfilial,
                'codgrupousuariousuario' => $value->codgrupousuariousuario
            ];
        }

        $arr = array_unique($result, SORT_REGULAR);

       return $arr;

    }
}
