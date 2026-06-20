<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTipoSetorController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codtiposetor, tiposetor, codtiposetor as value, tiposetor as label
            from tbltiposetor
            where inativo is null
              and (tiposetor ilike :busca)
            ORDER BY tiposetor
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codtiposetor, tiposetor, codtiposetor as value, tiposetor as label
            from tbltiposetor
            where codtiposetor = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
