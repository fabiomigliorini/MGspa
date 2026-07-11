<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectCfopController extends Controller
{
    public static function index(Request $request)
    {
        $sql = "
            select
                codcfop,
                cfop,
                codcfop as value,
                codcfop || ' - ' || cfop as label
            from tblcfop
            where (
                cast(codcfop as varchar) ilike :busca
                or cfop ilike :busca
            )
            order by codcfop
        ";
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = "
            select
                codcfop,
                cfop,
                codcfop as value,
                codcfop || ' - ' || cfop as label
            from tblcfop
            where codcfop = :id
            limit 1
        ";
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
