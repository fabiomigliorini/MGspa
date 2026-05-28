<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectGrupoEconomicoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codgrupoeconomico, grupoeconomico
            from tblgrupoeconomico
            where inativo is null
        ';

        if (!empty($request->codgrupoeconomico)) {
            $sql .= ' and codgrupoeconomico = :codgrupoeconomico';
            return response()->json(
                DB::select($sql, ['codgrupoeconomico' => $request->codgrupoeconomico]),
                200
            );
        }

        $sql .= ' and grupoeconomico ilike :busca ORDER BY grupoeconomico LIMIT 50';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
