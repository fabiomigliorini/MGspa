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
            select codtributacao, tributacao, codtributacao as value, tributacao as label
            from tbltributacao
            ORDER BY tributacao
        ';
        return response()->json(DB::select($sql), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codtributacao, tributacao, codtributacao as value, tributacao as label
            from tbltributacao
            where codtributacao = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
