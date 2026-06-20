<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectEtniaController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codetnia, etnia, codetnia as value, etnia as label
            from tbletnia
            where inativo is null
              and (etnia ilike :busca)
            ORDER BY etnia
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codetnia, etnia, codetnia as value, etnia as label
            from tbletnia
            where codetnia = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
