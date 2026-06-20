<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectUsuarioController extends Controller
{
    public static function index(Request $request)
    {
        $page = (int) $request->page > 0 ? (int) $request->page : 1;
        $offset = ($page - 1) * 20;

        $sql = '
            select
                u.codusuario, u.usuario, u.codpessoa, u.codfilial,
                f.filial, p.fantasia, p.pessoa, u.inativo,
                u.codusuario as value, u.usuario as label
            from tblusuario u
            left join tblfilial f on (f.codfilial = u.codfilial)
            left join tblpessoa p on (p.codpessoa = u.codpessoa)
            where (u.usuario ilike :busca or p.fantasia ilike :busca or p.pessoa ilike :busca)
        ';

        if (filter_var($request->somenteAtivos, FILTER_VALIDATE_BOOL)) {
            $sql .= ' and u.inativo is null ';
        }

        $sql .= ' ORDER BY u.usuario, u.codusuario LIMIT 20 OFFSET ' . $offset;
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select
                u.codusuario, u.usuario, u.codpessoa, u.codfilial,
                f.filial, p.fantasia, p.pessoa, u.inativo,
                u.codusuario as value, u.usuario as label
            from tblusuario u
            left join tblfilial f on (f.codfilial = u.codfilial)
            left join tblpessoa p on (p.codpessoa = u.codpessoa)
            where u.codusuario = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
