<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectGrupoClienteController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codgrupocliente, grupocliente, codgrupocliente as value, grupocliente as label
            from tblgrupocliente
            where inativo is null
              and (grupocliente ilike :busca)
            ORDER BY grupocliente
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codgrupocliente, grupocliente, codgrupocliente as value, grupocliente as label
            from tblgrupocliente
            where codgrupocliente = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
