<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectSetorController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);
        $sql = '
            select s.codsetor,
                   s.setor,
                   s.inativo,
                   un.descricao as unidadenegocio,
                   s.codsetor as value,
                   trim(s.setor || coalesce(\' - \' || un.descricao, \'\')) as label
            from tblsetor s
            left join tblunidadenegocio un on (un.codunidadenegocio = s.codunidadenegocio)
            where (s.setor ilike :busca or un.descricao ilike :busca)
        ';
        if (!$inativos) {
            $sql .= ' and s.inativo is null';
        }
        $sql .= ' ORDER BY s.setor';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select s.codsetor,
                   s.setor,
                   s.inativo,
                   un.descricao as unidadenegocio,
                   s.codsetor as value,
                   trim(s.setor || coalesce(\' - \' || un.descricao, \'\')) as label
            from tblsetor s
            left join tblunidadenegocio un on (un.codunidadenegocio = s.codunidadenegocio)
            where s.codsetor = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
