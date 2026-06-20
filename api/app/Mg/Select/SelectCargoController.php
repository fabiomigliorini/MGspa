<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectCargoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codcargo, cargo, codcargo as value, cargo as label
            from tblcargo
            where inativo is null
              and (cargo ilike :busca)
            ORDER BY cargo
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codcargo, cargo, codcargo as value, cargo as label
            from tblcargo
            where codcargo = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
