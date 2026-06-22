<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectCidadeController extends Controller
{
    public static function index(Request $request)
    {
        $page = (int) $request->page > 0 ? (int) $request->page : 1;
        $offset = ($page - 1) * 20;

        $sql = "
            select c.codcidade as value, c.cidade || ' / ' || e.sigla as label
            from tblcidade c
            inner join tblestado e on (e.codestado = c.codestado)
        ";

        $params = [];
        if (!empty($request->busca)) {
            $busca = trim(removeAcentos($request->busca));
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

        $sql .= ' order by c.cidade, e.estado, c.codcidade limit 20 offset ' . $offset;
        return DB::select($sql, $params);
    }

    public static function show($id)
    {
        $sql = "
            select c.codcidade as value, c.cidade || ' / ' || e.sigla as label
            from tblcidade c
            inner join tblestado e on (e.codestado = c.codestado)
            where c.codcidade = :id
            limit 1
        ";
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
