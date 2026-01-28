<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;


class SelectTipoTituloController extends Controller
{
    public static function index(Request $request)
    {
        // DADOS DE RETORNO
        $sql = '
            select
                codtipotitulo,
                tipotitulo
            from tbltipotitulo
        ';

        // SE PEDIU PELO CODIGO RETORNA REGISTRO
        if (!empty($request->codtipotitulo)) {
            $sql .= ' where codtipotitulo = :codtipotitulo';
            $data = DB::select($sql, ['codtipotitulo' => $request->codtipotitulo]);
            return response()->json($data, 200);
        }

        // SENAO FILTRA PELO TEXTO
        $sql .= '
            where (
                tipotitulo ilike :busca
            )
        ';

        $sql .= ' ORDER BY tipotitulo LIMIT 50';

        // RETORNA
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
