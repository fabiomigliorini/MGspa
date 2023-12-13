<?php

namespace Mg\Permissao\Autorizador;

class Autorizador 
{

    public static function pode($permissao, $codfilial = null, $codusuario = null) 
    {
        $sql = '
            select :permissao
            from tblpermissao p
            left join tblgrupousuariopermissao gup on (gup.codpermissao = p.codpermissao)
            left join tblgrupousuariousuario guu on (guu.codgrupousuario = gup.codgrupousuario)
            where guu.codusuario = :codusuario
        ';
        if (empty($codusuario)) {
            $codusuario = Auth::user()->id;
        }
        $params = [
            'codusuario' => $codusuario,
            'permissao' => $permissao
        ];

        if (!empty($codfilial)) {
            $sql .= 'and guu.codfilial = :codfilial';
            $params['codfilial'] = $codfilial;
        }

    }

    public static function autoriza($permissao, $codfilial = null, $codusuario = null) 
    {
        if (!static::pode($permissao, $codfilial = null, $codusuario = null)) {
            abort('403');
        }
    }


}

