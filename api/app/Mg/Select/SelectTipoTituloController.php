<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTipoTituloController extends Controller
{
    public static function index(Request $request)
    {
        $sql = 'select codtipotitulo, tipotitulo from tbltipotitulo';

        if (!empty($request->codtipotitulo)) {
            $sql .= ' where codtipotitulo = :codtipotitulo';
            return response()->json(
                DB::select($sql, ['codtipotitulo' => $request->codtipotitulo]),
                200
            );
        }

        $sql .= ' where (tipotitulo ilike :busca) ORDER BY tipotitulo LIMIT 50';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
