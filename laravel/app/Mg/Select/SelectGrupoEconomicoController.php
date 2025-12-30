<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;


class SelectGrupoEconomicoController extends Controller
{
    public static function index(Request $request)
    {

        // DADOS DE RETORNO
        $sql = '
            select
                codgrupoeconomico,
                grupoeconomico
            from tblgrupoeconomico
            where inativo is null
        ';

        // SE PEDIU PELO CODIGO RETORNA REGISTRO
        if (!empty($request->codgrupoeconomico)) {
            $sql .= ' and codgrupoeconomico = :codgrupoeconomico';
            $data = DB::select($sql, ['codgrupoeconomico' => $request->codgrupoeconomico]);
            return response()->json($data, 200);
        }

        // SENAO FILTRA PELO TEXTO
        $sql .= '
            and grupoeconomico ilike :busca
        ';

        $sql .= ' ORDER BY grupoeconomico LIMIT 50';

        // RETORNA
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
