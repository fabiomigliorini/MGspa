<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

use Mg\Produto\ProdutoBarra;

class SelectUsuarioController extends Controller
{
    public static function index(Request $request)
    {

        // DADOS DE RETORNO
        $sql = '
            select 
                u.codusuario,
                u.usuario,
                u.codpessoa,
                u.codfilial,
                f.filial,
                p.fantasia,
                p.pessoa,
                u.inativo
            from tblusuario u
            left join tblfilial f on (f.codfilial = u.codfilial)
            left join tblpessoa p on (p.codpessoa = u.codpessoa)
        ';

        // SE PEDIU PELO CODIGO RETORNA REGISTRO
        if (!empty($request->codusuario)) {
            $sql .= ' where u.codusuario = :codusuario';
            $data = DB::select($sql, ['codusuario' => $request->codusuario]);
            return response()->json($data, 200);
        }

        // SENAO FILTRA PELO TEXTO
        $sql .= ' 
            where (
                u.usuario ilike :busca 
                or p.fantasia ilike :busca
                or p.pessoa ilike :busca
            )
        ';

        // SE SOMENTE ATIVOS
        if (filter_var($request->somenteAtivos, FILTER_VALIDATE_BOOL)) {
            $sql .= ' and u.inativo is null ';
        }

        $sql .= ' ORDER BY u.usuario, u.codusuario LIMIT 50';

        // RETORNA
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $data = DB::select($sql, ['busca' => "%{$busca}%"]);
        return response()->json($data, 200);
    }
}
