<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Impressoras do cadastro (tblimpressora, TASK-253). value = nome da fila no CUPS
// (o que as rotas de impressao recebem hoje); tipo filtra (?tipo=T,A).
class SelectImpressoraController extends Controller
{
    const SQL = '
        select
            i.codimpressora, i.impressora, i.descricao, i.tipo, i.inativo,
            i.impressora as value, i.descricao as label
        from tblimpressora i
    ';

    public static function index(Request $request)
    {
        $sql = static::SQL . ' where true';
        $bindings = [];
        if (!filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN)) {
            $sql .= ' and i.inativo is null';
        }
        $tipos = array_filter(explode(',', (string) $request->tipo));
        if (!empty($tipos)) {
            $sql .= " and i.tipo = any(string_to_array(:tipos, ','))";
            $bindings['tipos'] = implode(',', $tipos);
        }
        $sql .= ' order by i.descricao, i.codimpressora';
        return response()->json(DB::select($sql, $bindings), 200);
    }

    public static function show($id)
    {
        $rows = DB::select(static::SQL . ' where i.impressora = :id limit 1', ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
