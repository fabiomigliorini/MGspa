<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class SelectTipoProdutoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select
                codtipoproduto,
                tipoproduto
            from tbltipoproduto
        ';

        // SE PEDIU PELO CODIGO RETORNA REGISTRO
        if (!empty($request->codtipoproduto)) {
            $sql .= ' where codtipoproduto = :codtipoproduto';
            $data = DB::select($sql, ['codtipoproduto' => $request->codtipoproduto]);
            return response()->json($data, 200);
        }

        // SENAO FILTRA PELO TEXTO
        $sql .= '
            where tipoproduto ilike :busca
        ';

        $sql .= ' ORDER BY tipoproduto LIMIT 50';

        // RETORNA
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
