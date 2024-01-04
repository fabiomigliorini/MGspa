<?php

namespace Mg\Permissao\Autorizador;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Autorizador 
{

    public static function pode($permissao, $codfilial = null, $codusuario = null) 
    {
        if (empty($codusuario)) {
            $codusuario = Auth::user()->codusuario;
        }

        $sql = '
            select count(*) as count
            from tblpermissao p
            left join tblgrupousuariopermissao gup on (gup.codpermissao = p.codpermissao)
            left join tblgrupousuariousuario guu on (guu.codgrupousuario = gup.codgrupousuario)
            where guu.codusuario = :codusuario
            and p.permissao = :permissao
        ';
        
        $params = [
            'codusuario' => $codusuario,
            'permissao' => $permissao
        ];

        if (!empty($codfilial)) {
            $sql .= 'and guu.codfilial = :codfilial';
            $params['codfilial'] = $codfilial;
        }

        $ret = DB::select($sql, $params);
        return ($ret[0]->count > 0);
    }

    public static function autoriza($permissao, $codfilial = null, $codusuario = null) 
    {
        if (!static::pode($permissao, $codfilial, $codusuario)) {
            abort('403', 'Não Autorizado!');
        }
        return true;
    }

}
