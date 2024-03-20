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


    public static function buscaGrupoPermissoes($codusuario)
    {
        $sql = '
            select guu.codgrupousuariousuario, gu.grupousuario, gu.codgrupousuario, fi.filial, guu.codfilial
            from tblgrupousuariousuario guu
            inner join tblgrupousuario gu on (gu.codgrupousuario = guu.codgrupousuario)
            inner join tblfilial fi on (fi.codfilial = guu.codfilial)
            where guu.codusuario = :codusuario
        ';
        $params['codusuario'] = $codusuario;
        return DB::select($sql, $params);
    }


    public static function autalizaPermissoes(Usuario $usuario, $permissoes)
    {
        foreach ($permissoes as $codgrupousuario => $filiais) {
            foreach ($filiais as $codfilial => $value) {
                $existe = $usuario->GrupoUsuarioUsuarioS()->where('codgrupousuario', $codgrupousuario)->where('codfilial', $codfilial)->count();
                if ($value == false && $existe) {
                    // Deleta os grupos que estiver marcado como false e existir no banco
                    $usuario->GrupoUsuarioUsuarioS()->where('codgrupousuario', $codgrupousuario)->where('codfilial', $codfilial)->delete();
                } else if ($value && !$existe) {
                    // se tiver marcado como true e não existir no banco add 
                    $grupoUsuario = new GrupoUsuarioUsuario([
                        'codusuario'        => $usuario->codusuario,
                        'codgrupousuario'   => $codgrupousuario,
                        'codfilial'         => $codfilial
                    ]);
                    $grupoUsuario->save();
                }
            }
        }
        return $usuario;
    }

    public static function updateUsuario(Usuario $usuario, $data)
    {
        $usuario->fill($data);
        $usuario->save();
        static::autalizaPermissoes($usuario, $data['permissoes']);
        return $usuario;
    }


    public static function create($data)
    {
        $usuario = new Usuario($data);
        $usuario->save();
        static::autalizaPermissoes($usuario, $data['permissoes']);
        return $usuario;
    }


    public static function resetarSenha($usuario)
    {

        $pwd = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);

        $usuario->fill([
            'senha' => $pwd
        ]);
        $usuario->senha = bcrypt($usuario->senha);

        $usuario->update();

        return $pwd;

    }
}
