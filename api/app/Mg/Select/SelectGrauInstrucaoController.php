<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectGrauInstrucaoController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);
        $sql = '
            select codgrauinstrucao, grauinstrucao, inativo, codgrauinstrucao as value, grauinstrucao as label
            from tblgrauinstrucao
            where (grauinstrucao ilike :busca)
        ';
        if (!$inativos) {
            $sql .= ' and inativo is null';
        }
        $sql .= ' ORDER BY grauinstrucao';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select codgrauinstrucao, grauinstrucao, inativo, codgrauinstrucao as value, grauinstrucao as label
            from tblgrauinstrucao
            where codgrauinstrucao = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
