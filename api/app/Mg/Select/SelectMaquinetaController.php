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
        $bindings = [];
        // busca (campo de busca remota): apelido, serial, filial ou adquirente
        if (trim((string) $request->busca) !== '') {
            $sql .= ' and (m.apelido ilike :busca or m.serial ilike :busca or f.filial ilike :busca or p.fantasia ilike :busca)';
            $busca = preg_replace('/\s+/', '%', trim($request->busca));
            $bindings['busca'] = "%{$busca}%";
        }
        $sql .= ' order by m.apelido, f.filial, m.codmaquineta';
        // com page: 20 por pagina; sem, a lista toda (ate 500)
        if ((int) $request->page > 0) {
            $sql .= ' limit 20 offset ' . (((int) $request->page - 1) * 20);
        } else {
            $sql .= ' limit 500';
        }
        return response()->json(DB::select($sql, $bindings), 200);
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
