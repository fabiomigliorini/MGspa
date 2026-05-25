<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectBancoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codbanco, banco, sigla, numerobanco
            from tblbanco
        ';

        if (!empty($request->codbanco)) {
            $sql .= ' where codbanco = :codbanco';
            return response()->json(DB::select($sql, ['codbanco' => $request->codbanco]), 200);
        }

        $sql .= '
            where inativo is null
              and (banco ilike :busca or sigla ilike :busca or numerobanco::text ilike :busca)
            ORDER BY banco LIMIT 50
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
