<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTipoProdutoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = 'select codtipoproduto, tipoproduto from tbltipoproduto';

        if (!empty($request->codtipoproduto)) {
            $sql .= ' where codtipoproduto = :codtipoproduto';
            return response()->json(
                DB::select($sql, ['codtipoproduto' => $request->codtipoproduto]),
                200
            );
        }

        $sql .= ' where tipoproduto ilike :busca ORDER BY tipoproduto LIMIT 50';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
