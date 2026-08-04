<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectRubricaController extends Controller
{
    // Alem de value/label, devolve os campos do catalogo usados como snapshot ao
    // vincular a rubrica no colaborador (tipovalor, tipocondicao, recorrente e
    // os valores padrao) — o MgSelectRubrica emite a opcao inteira no @select.
    const COLUNAS = '
        codrubrica, descricao, tipovalor, tipocondicao,
        valorpadrao, valorunitariopadrao, recorrente, inativo,
        codrubrica as value, descricao as label
    ';

    // DB::select (SQL cru) devolve numeric como string; o front usa esses valores
    // como numero pra pre-preencher o formulario. Casta igual aos $casts do Model.
    private static function casta($row)
    {
        $row->valorpadrao = isset($row->valorpadrao) ? (float) $row->valorpadrao : null;
        $row->valorunitariopadrao = isset($row->valorunitariopadrao) ? (float) $row->valorunitariopadrao : null;
        return $row;
    }

    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);
        $sql = '
            select ' . static::COLUNAS . '
            from tblrubrica
            where (descricao ilike :busca)
        ';
        if (!$inativos) {
            $sql .= ' and inativo is null';
        }
        $sql .= ' ORDER BY descricao';
        $busca = preg_replace('/\s+/', '%', trim($request->busca));
        $rows = array_map([static::class, 'casta'], DB::select($sql, ['busca' => "%{$busca}%"]));
        return response()->json($rows, 200);
    }

    public static function show($id)
    {
        $sql = '
            select ' . static::COLUNAS . '
            from tblrubrica
            where codrubrica = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return static::casta($rows[0]);
    }
}