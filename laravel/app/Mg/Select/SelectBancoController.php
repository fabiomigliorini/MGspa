<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class SelectBancoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select
                codbanco,
                banco,
                sigla,
                numerobanco
            from tblbanco
        ';

        if (!empty($request->codbanco)) {
            $sql .= ' where codbanco = :codbanco';
            $data = DB::select($sql, ['codbanco' => $request->codbanco]);
            return response()->json($data, 200);
        }

        $sql .= '
            where inativo is null
              and (
                banco ilike :busca
                or sigla ilike :busca
                or numerobanco ilike :busca
              )
        ';

        $sql .= ' ORDER BY banco LIMIT 50';

        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
