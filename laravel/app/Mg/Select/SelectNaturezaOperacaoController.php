<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class SelectNaturezaOperacaoController extends Controller
{
    public static function index(Request $request)
    {

        // DADOS DE RETORNO
        $sql = '
            select 
                nat.codnaturezaoperacao,
                op.operacao,
                nat.naturezaoperacao
            from tblnaturezaoperacao nat
            inner join tbloperacao op on (op.codoperacao = nat.codoperacao)
        ';

        // SE PEDIU PELO CODIGO RETORNA REGISTRO
        if (!empty($request->codnaturezaoperacao)) {
            $sql .= ' where nat.codnaturezaoperacao = :codnaturezaoperacao';
            $data = DB::select($sql, ['codnaturezaoperacao' => $request->codnaturezaoperacao]);
            return response()->json($data, 200);
        }

        // SENAO FILTRA PELO TEXTO
        $sql .= ' 
            where (
                nat.naturezaoperacao ilike :busca 
            )
        ';

        $sql .= ' ORDER BY op.codoperacao, nat.naturezaoperacao LIMIT 50';

        // RETORNA
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
