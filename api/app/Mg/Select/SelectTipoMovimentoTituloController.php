<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTipoMovimentoTituloController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codtipomovimentotitulo, tipomovimentotitulo, codtipomovimentotitulo as value, tipomovimentotitulo as label
            from tbltipomovimentotitulo
            where inativo is null
              and (tipomovimentotitulo ilike :busca)
            ORDER BY tipomovimentotitulo
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codtipomovimentotitulo, tipomovimentotitulo, codtipomovimentotitulo as value, tipomovimentotitulo as label
            from tbltipomovimentotitulo
            where codtipomovimentotitulo = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
