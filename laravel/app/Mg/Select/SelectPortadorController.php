<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use Mg\Produto\ProdutoBarra;

class SelectPortadorController extends Controller
{
    public static function index(Request $request)
    {

        // DADOS DE RETORNO
        $sql = '
            select 
                p.codportador,
                p.portador,
                p.codfilial,
                f.filial,
                p.codbanco,
                b.banco,
                p.agencia,
                p.agenciadigito,
                p.conta,
                p.contadigito,
                p.inativo
            from tblportador p
            left join tblfilial f on (f.codfilial = p.codfilial)
            left join tblbanco b on (b.codbanco = p.codbanco)
        ';

        // SE PEDIU PELO CODIGO RETORNA REGISTRO
        if (!empty($request->codportador)) {
            $sql .= ' where p.codportador = :codportador';
            $data = DB::select($sql, ['codportador' => $request->codportador]);
            return response()->json($data, 200);
        }

        // SENAO FILTRA PELO TEXTO
        $sql .= ' 
            where (
                p.portador ilike :busca 
            )
        ';

        // SE SOMENTE ATIVOS
        if (filter_var($request->somenteAtivos, FILTER_VALIDATE_BOOL)) {
            $sql .= ' and p.inativo is null ';
        }

        $sql .= ' ORDER BY p.portador, p.codportador LIMIT 250';

        // RETORNA
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
