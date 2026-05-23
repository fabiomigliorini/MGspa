<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectCidadeController extends Controller
{
    public static function index(Request $request)
    {
        $sql = "
            select c.codcidade as value, c.cidade || ' / ' || e.sigla as label
            from tblcidade c
            inner join tblestado e on (e.codestado = c.codestado)
        ";

        $params = [];
        if (!empty($request->codcidade)) {
            $sql .= ' where c.codcidade = :codcidade';
            $params['codcidade'] = $request->codcidade;
        }

        if (!empty($request->cidade)) {
            $busca = trim(removeAcentos($request->cidade));
            $palavras = explode(' ', preg_replace('/\s+/', ' ', $busca));
            $where = 'where';
            $ipalavra = 0;
            foreach ($palavras as $palavra) {
                $sql .= " {$where} c.cidade || ' / ' || e.sigla ilike :palavra{$ipalavra}";
                $params["palavra{$ipalavra}"] = "%{$palavra}%";
                $where = 'and';
                $ipalavra++;
            }
        }

        $sql .= ' order by c.cidade, e.estado, c.codcidade limit 100';
        return DB::select($sql, $params);
    }
}
