<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectMoedaController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);

        // value = codmoeda (FK inteira em tblcontratofixacao.codmoeda). label = sigla.
        $sql = '
            select codmoeda, moeda, sigla, iso, inativo, codmoeda as value, sigla as label
            from tblmoeda
            where (moeda ilike :busca or sigla ilike :busca or iso ilike :busca)
        ';
        if (!$inativos) {
            $sql .= ' and inativo is null';
        }
        $sql .= ' ORDER BY moeda';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codmoeda, moeda, sigla, iso, inativo, codmoeda as value, sigla as label
            from tblmoeda
            where codmoeda = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
