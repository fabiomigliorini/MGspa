<?php

namespace Mg\Usuario;

use Illuminate\Support\Facades\DB;
use Mg\MgService;

class UsuarioService extends MgService
{
    public static function detalhes($id): Usuario
    {
        return Usuario::findOrFail($id);
    }

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Usuario::query();

        if (!empty($filter['inativo'])) {
            if ($filter['inativo'] == 1) {
                $qry->whereNull('tblusuario.inativo');
            } elseif ($filter['inativo'] == 2) {
                $qry->whereNotNull('tblusuario.inativo');
            }
        }

        if (!empty($filter['usuario'])) {
            foreach (explode(' ', trim($filter['usuario'])) as $palavra) {
                if (!empty($palavra)) {
                    $qry->where('usuario', 'ilike', "%$palavra%");
                }
            }
        }

        if (!empty($filter['grupo'])) {
            $qry->whereIn('codusuario', function ($qry2) use ($filter) {
                $qry2->select('codusuario')
                    ->from('tblgrupousuariousuario')
                    ->where('codgrupousuario', $filter['grupo']);
            });
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function buscaGrupoPermissoes(int $codusuario): array
    {
        $sql = '
            select guu.codgrupousuariousuario, gu.grupousuario, gu.codgrupousuario, fi.filial, guu.codfilial
            from tblgrupousuariousuario guu
            inner join tblgrupousuario gu on (gu.codgrupousuario = guu.codgrupousuario)
            inner join tblfilial fi on (fi.codfilial = guu.codfilial)
            where guu.codusuario = :codusuario
        ';
        return DB::select($sql, ['codusuario' => $codusuario]);
    }

    public static function gruposDoUsuario(int $codusuario): array
    {
        static $cache = [];
        if (isset($cache[$codusuario])) {
            return $cache[$codusuario];
        }
        $rows = DB::select(
            'select distinct gu.grupousuario
               from tblgrupousuariousuario guu
               inner join tblgrupousuario gu on gu.codgrupousuario = guu.codgrupousuario
              where guu.codusuario = :codusuario',
            ['codusuario' => $codusuario]
        );
        return $cache[$codusuario] = array_map(fn ($r) => $r->grupousuario, $rows);
    }

    public static function temGrupo(int $codusuario, string $grupo): bool
    {
        return in_array($grupo, self::gruposDoUsuario($codusuario), true);
    }

    public static function filiaisDoUsuarioNoGrupo(int $codusuario, string $grupo): array
    {
        $rows = DB::select(
            'select guu.codfilial
               from tblgrupousuariousuario guu
               inner join tblgrupousuario gu on gu.codgrupousuario = guu.codgrupousuario
              where guu.codusuario = :codusuario
                and gu.grupousuario = :grupo
                and guu.codfilial is not null',
            ['codusuario' => $codusuario, 'grupo' => $grupo]
        );
        return array_map(fn ($r) => (int) $r->codfilial, $rows);
    }

    public static function atualizaPermissoes(Usuario $usuario, array $permissoes): Usuario
    {
        foreach ($permissoes as $codgrupousuario => $filiais) {
            foreach ($filiais as $codfilial => $value) {
                $existe = $usuario->GrupoUsuarioUsuarioS()
                    ->where('codgrupousuario', $codgrupousuario)
                    ->where('codfilial', $codfilial)
                    ->count();
                if (!$value && $existe) {
                    $usuario->GrupoUsuarioUsuarioS()
                        ->where('codgrupousuario', $codgrupousuario)
                        ->where('codfilial', $codfilial)
                        ->delete();
                } elseif ($value && !$existe) {
                    GrupoUsuarioUsuario::create([
                        'codusuario' => $usuario->codusuario,
                        'codgrupousuario' => $codgrupousuario,
                        'codfilial' => $codfilial,
                    ]);
                }
            }
        }
        return $usuario;
    }

    public static function updateUsuario(Usuario $usuario, array $data): Usuario
    {
        $usuario->fill($data);
        $usuario->save();
        static::atualizaPermissoes($usuario, $data['permissoes'] ?? []);
        return $usuario;
    }

    public static function create(array $data): Usuario
    {
        $usuario = new Usuario($data);
        if (!empty($data['senha'])) {
            $usuario->senha = bcrypt($data['senha']);
        }
        $usuario->save();
        static::atualizaPermissoes($usuario, $data['permissoes'] ?? []);
        return $usuario;
    }

    public static function resetarSenha(Usuario $usuario): string
    {
        $pwd = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $usuario->senha = bcrypt($pwd);
        $usuario->save();
        return $pwd;
    }
}
