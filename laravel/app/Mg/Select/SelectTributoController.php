<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SelectTributoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select
                codtributo,
                codigo,
                descricao,
                ente
            from tbltributo
            ORDER BY codigo, ente LIMIT 50
        ';

        // RETORNA
        $data = DB::select($sql);
        return response()->json($data, 200);
    }
}
