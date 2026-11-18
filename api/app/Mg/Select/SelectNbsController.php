<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * NBS da NFS-e (TASK-279). Com codtributacaonacional, so os NBS da correlacao oficial
 * do item da LC 116 (Anexo VIII): NBS fora dela pode ser recusado. Item sem
 * correlacao (ou sem codtributacaonacional) devolve todos.
 */
class SelectNbsController extends Controller
{
    const SQL = "
        select n.codnbs, n.codigo, n.descricao,
            n.codnbs as value,
            substr(n.codigo, 1, 1) || '.' || substr(n.codigo, 2, 4) || '.' || substr(n.codigo, 6, 2) || '.' || substr(n.codigo, 8, 2) || ' - ' || n.descricao as label
        from tblnbs n
    ";

    public static function index(Request $request)
    {
        $codtributacaonacional = (int) $request->get('codtributacaonacional');
        if ($codtributacaonacional) {
            $sql = static::SQL . '
                join tblnbsitem ni on ni.codnbs = n.codnbs
                join tbltributacaonacional t on t.item = ni.item
                where t.codtributacaonacional = :cod
                and n.inativo is null
                order by n.codigo
            ';
            $rows = DB::select($sql, ['cod' => $codtributacaonacional]);
            if (!empty($rows)) {
                return response()->json($rows, 200);
            }
        }
        $sql = static::SQL . ' where n.inativo is null order by n.codigo';
        return response()->json(DB::select($sql), 200);
    }

    public static function show($id)
    {
        $rows = DB::select(static::SQL . ' where n.codnbs = :id limit 1', ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
