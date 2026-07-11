<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTipoProdutoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codtipoproduto, tipoproduto, codtipoproduto as value, tipoproduto as label
            from tbltipoproduto
            where tipoproduto ilike :busca
            ORDER BY tipoproduto
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codtipoproduto, tipoproduto, codtipoproduto as value, tipoproduto as label
            from tbltipoproduto
            where codtipoproduto = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
