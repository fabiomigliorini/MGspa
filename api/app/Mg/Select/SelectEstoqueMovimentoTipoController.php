<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectEstoqueMovimentoTipoController extends Controller
{
    public static function index(Request $request)
    {
        $sql = 'select codestoquemovimentotipo, sigla, descricao from tblestoquemovimentotipo';

        if (!empty($request->codestoquemovimentotipo)) {
            $sql .= ' where codestoquemovimentotipo = :codestoquemovimentotipo';
            return response()->json(
                DB::select($sql, ['codestoquemovimentotipo' => $request->codestoquemovimentotipo]),
                200
            );
        }

        $sql .= '
            where (sigla ilike :busca or descricao ilike :busca)
            ORDER BY descricao LIMIT 50
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
