<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectEstoqueMovimentoTipoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select codestoquemovimentotipo, sigla, descricao, codestoquemovimentotipo as value, descricao as label
            from tblestoquemovimentotipo
            where (sigla ilike :busca or descricao ilike :busca)
            ORDER BY descricao
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codestoquemovimentotipo, sigla, descricao, codestoquemovimentotipo as value, descricao as label
            from tblestoquemovimentotipo
            where codestoquemovimentotipo = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
