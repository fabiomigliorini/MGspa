<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;


class SelectContaContabilController extends Controller
{
    public static function index(Request $request)
    {
        // DADOS DE RETORNO
        $sql = '
            select
                codcontacontabil,
                contacontabil,
                numero
            from tblcontacontabil
        ';

        // SE PEDIU PELO CODIGO RETORNA REGISTRO
        if (!empty($request->codcontacontabil)) {
            $sql .= ' where codcontacontabil = :codcontacontabil';
            $data = DB::select($sql, ['codcontacontabil' => $request->codcontacontabil]);
            return response()->json($data, 200);
        }

        // SENAO FILTRA PELO TEXTO
        $sql .= '
            where (
                contacontabil ilike :busca
                or numero ilike :busca
            )
        ';

        $sql .= ' ORDER BY contacontabil LIMIT 50';

        // RETORNA
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
