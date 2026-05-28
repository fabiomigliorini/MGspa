<?php

namespace Mg\Usuario;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Verificação de pertencimento a grupos de usuário.
 *
 * Porta literal de /opt/www/MGspa/laravel/app/Mg/Usuario/Autorizador.php
 * Lê de tblgrupousuariousuario + tblgrupousuario no banco mgsis
 * (compartilhado com MGspa/laravel). Sempre considera 'Administrador'
 * como liberado.
 */
class Autorizador
{
    public static function pode(array $gruposAutorizados, ?int $codfilial = null, ?int $codusuario = null): bool
    {
        if (empty($codusuario)) {
            $codusuario = Auth::user()->codusuario;
        }

        $whereGrupo = "and gu.grupousuario in ('Administrador'";
        foreach ($gruposAutorizados as $grupo) {
            $whereGrupo .= ", '{$grupo}'";
        }
        $whereGrupo .= ')';

        $sql = "
            select count(guu.codgrupousuariousuario) as count
            from tblgrupousuariousuario guu
            inner join tblgrupousuario gu on (gu.codgrupousuario = guu.codgrupousuario)
            where guu.codusuario = :codusuario
            {$whereGrupo}
        ";

        $params = ['codusuario' => $codusuario];

        if (!empty($codfilial)) {
            $sql .= 'and guu.codfilial = :codfilial';
            $params['codfilial'] = $codfilial;
        }

        $ret = DB::selectOne($sql, $params);
        return ($ret->count > 0);
    }

    public static function autoriza(array $gruposAutorizados, ?int $codfilial = null, ?int $codusuario = null): bool
    {
        if (!static::pode($gruposAutorizados, $codfilial, $codusuario)) {
            abort(403, 'Não Autorizado!');
        }
        return true;
    }
}
