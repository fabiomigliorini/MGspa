<?php

namespace Mg\Cidade;

use Illuminate\Support\Facades\DB;

class CidadeService
{

    public static function buscaPeloNomeUf($cidade, $uf)
    {
        $sql = '
            select c.*
            from tblcidade c
            inner join tblestado e on (e.codestado = c.codestado)
            where c.cidade ilike :cidade
            and e.sigla ilike :uf
        ';
        $ret = DB::select($sql, [
            'cidade' => $cidade,
            'uf' => $uf,
        ]);
        if (sizeof($ret) > 0) {
            return Cidade::hydrate((array) $ret)[0];
        }
        return false;
    }
}
