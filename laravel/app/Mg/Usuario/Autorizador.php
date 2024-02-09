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

        $sql = '
            select count(guu.codgrupousuariousuario) 
            from tblgrupousuariousuario guu
            inner join tblgrupousuario gu on (gu.codgrupousuario = guu.codgrupousuario)
            where guu.codusuario = :codusuario
            and gu.grupousuario in (:gruposAutorizados)
        ';
        

        $params = [
            'codusuario' => $codusuario,
            'gruposAutorizados' => implode(',', $gruposAutorizados)
        ];

        if (!empty($codfilial)) {
            $sql .= 'and guu.codfilial = :codfilial';
            $params['codfilial'] = $codfilial;
        }

        $ret = DB::select($sql, $params);
        return ($ret[0]->count > 0);
    }

    public static function autoriza($gruposAutorizados, $codfilial = null, $codusuario = null) 
    {
        if (!static::pode($gruposAutorizados, $codfilial, $codusuario)) {
            abort('403', 'Não Autorizado!');
        }
        return true;
    }
}
