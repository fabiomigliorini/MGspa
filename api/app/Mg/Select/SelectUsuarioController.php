<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectUsuarioController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select
                u.codusuario, u.usuario, u.codpessoa, u.codfilial,
                f.filial, p.fantasia, p.pessoa, u.inativo
            from tblusuario u
            left join tblfilial f on (f.codfilial = u.codfilial)
            left join tblpessoa p on (p.codpessoa = u.codpessoa)
        ';

        if (!empty($request->codusuario)) {
            $sql .= ' where u.codusuario = :codusuario';
            return response()->json(DB::select($sql, ['codusuario' => $request->codusuario]), 200);
        }

        $sql .= '
            where (u.usuario ilike :busca or p.fantasia ilike :busca or p.pessoa ilike :busca)
        ';

        if (filter_var($request->somenteAtivos, FILTER_VALIDATE_BOOL)) {
            $sql .= ' and u.inativo is null ';
        }

        $sql .= ' ORDER BY u.usuario, u.codusuario LIMIT 50';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
