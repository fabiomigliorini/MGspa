<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTributoController extends Controller
{
    public static function index(Request $request)
    {
        $page = (int) $request->page > 0 ? (int) $request->page : 1;
        $offset = ($page - 1) * 20;

        $sql = '
            select codtributo, codigo, descricao, ente, codtributo as value, descricao as label
            from tbltributo
            where (codigo ilike :busca or descricao ilike :busca or ente ilike :busca)
            ORDER BY codigo, ente LIMIT 20 OFFSET ' . $offset . '
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codtributo, codigo, descricao, ente, codtributo as value, descricao as label
            from tbltributo
            where codtributo = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
