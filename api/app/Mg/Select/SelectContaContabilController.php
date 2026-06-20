<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectContaContabilController extends Controller
{
    public static function index(Request $request)
    {
        $page = (int) $request->page > 0 ? (int) $request->page : 1;
        $offset = ($page - 1) * 20;
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);

        $sql = '
            select codcontacontabil, contacontabil, numero, inativo, codcontacontabil as value, contacontabil as label
            from tblcontacontabil
            where (contacontabil ilike :busca or numero ilike :busca)
        ';
        if (!$inativos) {
            $sql .= ' and inativo is null';
        }
        $sql .= ' ORDER BY contacontabil LIMIT 20 OFFSET ' . $offset;
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codcontacontabil, contacontabil, numero, inativo, codcontacontabil as value, contacontabil as label
            from tblcontacontabil
            where codcontacontabil = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
