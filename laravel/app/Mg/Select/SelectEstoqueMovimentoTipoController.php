<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class SelectEstoqueMovimentoTipoController extends Controller
{
    public static function index(Request $request)
    {
        // DADOS DE RETORNO
        $sql = '
            select
                codestoquemovimentotipo,
                sigla,
                descricao
            from tblestoquemovimentotipo
        ';

        // SE PEDIU PELO CODIGO RETORNA REGISTRO
        if (!empty($request->codestoquemovimentotipo)) {
            $sql .= ' where codestoquemovimentotipo = :codestoquemovimentotipo';
            $data = DB::select($sql, ['codestoquemovimentotipo' => $request->codestoquemovimentotipo]);
            return response()->json($data, 200);
        }

        // SENAO FILTRA PELO TEXTO
        $sql .= '
            where (
                sigla ilike :busca
                or descricao ilike :busca
            )
        ';

        $sql .= ' ORDER BY descricao LIMIT 50';

        // RETORNA
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
