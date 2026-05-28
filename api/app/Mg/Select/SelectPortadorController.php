<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectPortadorController extends Controller
{
    public static function index(Request $request)
    {
        $sql = '
            select
                p.codportador, p.portador, p.codfilial,
                f.filial, f.codempresa,
                p.codbanco, b.banco,
                p.agencia, p.agenciadigito,
                p.conta, p.contadigito,
                p.pixdict, p.inativo
            from tblportador p
            left join tblfilial f on (f.codfilial = p.codfilial)
            left join tblbanco b on (b.codbanco = p.codbanco)
        ';

        $somentePix = filter_var($request->somentePix, FILTER_VALIDATE_BOOL);
        $codfilial = $request->codfilial;

        if (!empty($request->codportador)) {
            $sql .= ' where p.codportador = :codportador';
            $bindings = ['codportador' => $request->codportador];

            if ($somentePix) {
                $sql .= ' and p.pixdict is not null and p.inativo is null';
            }
            if (!empty($codfilial)) {
                $sql .= ' and f.codempresa = (select codempresa from tblfilial where codfilial = :codfilial)';
                $bindings['codfilial'] = $codfilial;
            }
            return response()->json(DB::select($sql, $bindings), 200);
        }

        if ($somentePix) {
            $sql .= ' where p.pixdict is not null and p.inativo is null';
            if (!empty($codfilial)) {
                $sql .= ' and f.codempresa = (select codempresa from tblfilial where codfilial = :codfilial)';
            }
            $sql .= ' ORDER BY p.portador, p.codportador LIMIT 250';
            $bindings = !empty($codfilial) ? ['codfilial' => $codfilial] : [];
            return response()->json(DB::select($sql, $bindings), 200);
        }

        $sql .= ' where (p.portador ilike :busca)';
        if (filter_var($request->somenteAtivos, FILTER_VALIDATE_BOOL)) {
            $sql .= ' and p.inativo is null ';
        }
        $sql .= ' ORDER BY p.portador, p.codportador LIMIT 250';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
