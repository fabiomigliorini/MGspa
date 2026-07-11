<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectCertidaoEmissorController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);

        $sql = '
            select
                codcertidaoemissor,
                certidaoemissor,
                inativo,
                codcertidaoemissor as value,
                certidaoemissor as label
            from tblcertidaoemissor
            where (certidaoemissor ilike :busca)
        ';
        if (!$inativos) {
            $sql .= ' and inativo is null';
        }
        $sql .= ' order by certidaoemissor';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }

    public static function show($id)
    {
        $sql = '
            select
                codcertidaoemissor,
                certidaoemissor,
                inativo,
                codcertidaoemissor as value,
                certidaoemissor as label
            from tblcertidaoemissor
            where codcertidaoemissor = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
