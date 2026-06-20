<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectUnidadeNegocioController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codunidadenegocio, descricao, codunidadenegocio as value, descricao as label
            from tblunidadenegocio
            where inativo is null
              and (descricao ilike :busca)
            ORDER BY descricao
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codunidadenegocio, descricao, codunidadenegocio as value, descricao as label
            from tblunidadenegocio
            where codunidadenegocio = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
