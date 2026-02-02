<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class SelectTributacaoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select
                codtributacao,
                tributacao
            from tbltributacao
            ORDER BY tributacao
        ';

        $data = DB::select($sql);
        return response()->json($data, 200);
    }
}
