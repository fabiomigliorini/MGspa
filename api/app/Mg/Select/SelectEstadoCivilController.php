<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectEstadoCivilController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);
        $sql = '
            select codestadocivil, estadocivil, inativo, codestadocivil as value, estadocivil as label
            from tblestadocivil
            where (estadocivil ilike :busca)
        ';
        if (!$inativos) {
            $sql .= ' and inativo is null';
        }
        $sql .= ' ORDER BY estadocivil';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codestadocivil, estadocivil, inativo, codestadocivil as value, estadocivil as label
            from tblestadocivil
            where codestadocivil = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
