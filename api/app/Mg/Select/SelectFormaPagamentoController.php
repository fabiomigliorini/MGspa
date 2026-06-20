<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectFormaPagamentoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codformapagamento, formapagamento, codformapagamento as value, formapagamento as label
            from tblformapagamento
            where inativo is null
              and (formapagamento ilike :busca)
            ORDER BY formapagamento
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codformapagamento, formapagamento, codformapagamento as value, formapagamento as label
            from tblformapagamento
            where codformapagamento = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
