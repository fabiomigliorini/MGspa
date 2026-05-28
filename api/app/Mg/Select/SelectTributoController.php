<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTributoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codtributo, codigo, descricao, ente
            from tbltributo
            ORDER BY codigo, ente LIMIT 50
        ';
        return response()->json(DB::select($sql), 200);
    }
}
