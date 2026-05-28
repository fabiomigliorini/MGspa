<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTributacaoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codtributacao, tributacao
            from tbltributacao
            ORDER BY tributacao
        ';
        return response()->json(DB::select($sql), 200);
    }
}
