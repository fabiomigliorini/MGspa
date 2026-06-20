<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTipoTituloController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codtipotitulo, tipotitulo, codtipotitulo as value, tipotitulo as label
            from tbltipotitulo
            where (tipotitulo ilike :busca)
            ORDER BY tipotitulo
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codtipotitulo, tipotitulo, codtipotitulo as value, tipotitulo as label
            from tbltipotitulo
            where codtipotitulo = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
