<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// PDVs (dispositivos) para o filtro da listagem de pagamentos (M6.1 doc-3)
class SelectPdvController extends Controller
{
    const SQL = '
        select
            d.codpdv, d.apelido, d.codfilial, f.filial, d.inativo,
            d.codpdv as value, coalesce(d.apelido, \'PDV \' || d.codpdv) as label
        from tblpdv d
        left join tblfilial f on (f.codfilial = d.codfilial)
    ';

    public static function index(Request $request)
    {
        $sql = static::SQL . ' where d.autorizado';
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);
        if (!$inativos) {
            $sql .= ' and d.inativo is null';
        }
        $sql .= ' order by f.filial, d.apelido, d.codpdv limit 500';
        return response()->json(DB::select($sql), 200);
    }

    public static function show($id)
    {
        $rows = DB::select(static::SQL . ' where d.codpdv = :id limit 1', ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
