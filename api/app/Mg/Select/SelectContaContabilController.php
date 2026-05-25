<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectContaContabilController extends Controller
{
    public static function index(Request $request)
    {
        $sql = 'select codcontacontabil, contacontabil, numero from tblcontacontabil';

        if (!empty($request->codcontacontabil)) {
            $sql .= ' where codcontacontabil = :codcontacontabil';
            return response()->json(
                DB::select($sql, ['codcontacontabil' => $request->codcontacontabil]),
                200
            );
        }

        $sql .= '
            where (contacontabil ilike :busca or numero ilike :busca)
            ORDER BY contacontabil LIMIT 50
        ';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        return response()->json(DB::select($sql, ['busca' => "%{$busca}%"]), 200);
    }
}
