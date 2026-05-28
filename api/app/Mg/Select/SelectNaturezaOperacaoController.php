<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectNaturezaOperacaoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select nat.codnaturezaoperacao, op.operacao, nat.naturezaoperacao
            from tblnaturezaoperacao nat
            inner join tbloperacao op on (op.codoperacao = nat.codoperacao)
        ';

        if (!empty($request->codnaturezaoperacao)) {
            $sql .= ' where nat.codnaturezaoperacao = :codnaturezaoperacao';
            return response()->json(
                DB::select($sql, ['codnaturezaoperacao' => $request->codnaturezaoperacao]),
                200
            );
        }

        $sql .= '
            where (nat.naturezaoperacao ilike :busca)
            ORDER BY op.codoperacao, nat.naturezaoperacao LIMIT 50
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
