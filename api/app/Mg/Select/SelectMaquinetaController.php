<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectMaquinetaController extends Controller
{
    const SQL = '
        select
            m.codmaquineta, m.apelido, m.serial, m.codfilial, f.filial, m.compartilhada,
            m.codpessoa, p.fantasia as adquirente, m.integracao, m.inativo,
            m.codmaquineta as value, m.apelido as label
        from tblmaquineta m
        inner join tblfilial f on (f.codfilial = m.codfilial)
        inner join tblpessoa p on (p.codpessoa = m.codpessoa)
    ';

    public static function index(Request $request)
    {
        $sql = static::SQL . ' where true';
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);
        if (!$inativos) {
            $sql .= ' and m.inativo is null';
        }
        $sql .= ' order by m.apelido, f.filial, m.codmaquineta limit 500';
        return response()->json(DB::select($sql), 200);
    }

    public static function show($id)
    {
        $rows = DB::select(static::SQL . ' where m.codmaquineta = :id limit 1', ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
