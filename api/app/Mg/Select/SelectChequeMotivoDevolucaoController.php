<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectChequeMotivoDevolucaoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codchequemotivodevolucao, chequemotivodevolucao, codchequemotivodevolucao as value, chequemotivodevolucao as label
            from tblchequemotivodevolucao
            where (chequemotivodevolucao ilike :busca)
            ORDER BY chequemotivodevolucao
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codchequemotivodevolucao, chequemotivodevolucao, codchequemotivodevolucao as value, chequemotivodevolucao as label
            from tblchequemotivodevolucao
            where codchequemotivodevolucao = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
