<?php

namespace Mg\Usuario;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Autorizador 
{

    public static function pode(Array $gruposAutorizados, int $codfilial = null, int $codusuario = null) 
    {
        if (empty($codusuario)) {
            $codusuario = Auth::user()->codusuario;
        }

        $whereGrupo = 'and gu.grupousuario in (\'Administrador\'';
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
        
        $params = [
            'codusuario' => $codusuario,
        ];

        if (!empty($codfilial)) {
            $sql .= 'and guu.codfilial = :codfilial';
            $params['codfilial'] = $codfilial;
        }

        $ret = DB::selectOne($sql, $params);
        return ($ret->count > 0);
    }

    public static function autoriza($gruposAutorizados, $codfilial = null, $codusuario = null) 
    {
        if (!static::pode($gruposAutorizados, $codfilial, $codusuario)) {
            abort('403', 'Não Autorizado!');
        }
        return true;
    }
}
