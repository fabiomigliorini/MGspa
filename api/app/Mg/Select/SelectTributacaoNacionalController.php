<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Codigo de tributacao nacional da NFS-e (TASK-279). ~340 linhas: o front filtra.
class SelectTributacaoNacionalController extends Controller
{
    const SQL = "
        select codtributacaonacional, codigo, item, descricao,
            codtributacaonacional as value,
            substr(codigo, 1, 2) || '.' || substr(codigo, 3, 2) || '.' || substr(codigo, 5, 2) || ' - ' || descricao as label
        from tbltributacaonacional
    ";

    public static function index(Request $request)
    {
        $sql = static::SQL . ' where inativo is null order by codigo';
        return response()->json(DB::select($sql), 200);
    }

    public static function show($id)
    {
        $rows = DB::select(static::SQL . ' where codtributacaonacional = :id limit 1', ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
