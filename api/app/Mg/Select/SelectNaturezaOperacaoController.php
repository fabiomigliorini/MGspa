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
            select nat.codnaturezaoperacao, op.operacao, nat.naturezaoperacao, nat.codnaturezaoperacao as value, nat.naturezaoperacao as label
            from tblnaturezaoperacao nat
            inner join tbloperacao op on (op.codoperacao = nat.codoperacao)
            where (nat.naturezaoperacao ilike :busca or op.operacao ilike :busca)
            ORDER BY op.codoperacao, nat.naturezaoperacao, nat.codnaturezaoperacao
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select nat.codnaturezaoperacao, op.operacao, nat.naturezaoperacao, nat.codnaturezaoperacao as value, nat.naturezaoperacao as label
            from tblnaturezaoperacao nat
            inner join tbloperacao op on (op.codoperacao = nat.codoperacao)
            where nat.codnaturezaoperacao = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
