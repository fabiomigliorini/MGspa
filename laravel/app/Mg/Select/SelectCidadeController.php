<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

use Mg\Cidade\Cidade;

class SelectCidadeController extends Controller
{
    public static function index(Request $request)
    {
        // busca Cidades
        $sql = "
            select c.codcidade as value, c.cidade || ' / ' || e.estado as label
            from tblcidade c
            inner join tblestado e on (e.codestado = c.codestado)
        ";

        $params = [];
        if (!empty($request->codcidade)) {
            $sql .= '
            where c.codcidade = :codcidade
            ';
            $params['codcidade'] = $request->codcidade;
        }
        if (!empty($request->cidade)) {
            $palavras = preg_replace('/\s+/', ' ', $request->cidade);
            $palavras = explode(' ', $palavras);
            $where = 'where';
            $ipalavra = 0;
            foreach ($palavras as $palavra) {
                $sql .= "
                 {$where} c.cidade || ' / ' || e.estado ilike :palavra{$ipalavra}
                ";
                $params["palavra{$ipalavra}"] = "%{$palavra}%";
                $where = 'and';
                $ipalavra++;
            }
        }
        $sql .='
            order by c.cidade, e.estado, c.codcidade
            limit 100
        ';
        $ret = DB::select($sql, $params);

        // retorna
        return $ret;
    }
}
