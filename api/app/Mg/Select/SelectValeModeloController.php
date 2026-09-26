<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// REMOTE com pagina de 40 (e nao 20): o select abre ja listando os modelos,
// sem precisar digitar, e o catalogo de uma escola cabe numa pagina.
class SelectValeModeloController extends Controller
{
    const POR_PAGINA = 40;

    public static function index(Request $request)
    {
        $page = (int) $request->page > 0 ? (int) $request->page : 1;
        $offset = ($page - 1) * static::POR_PAGINA;
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);

        $sql = '
            select vm.codvalemodelo, vm.modelo, vm.inativo, vm.codpessoafavorecido, p.fantasia as favorecido,
                   vm.codvalemodelo as value, vm.modelo as label
            from tblvalemodelo vm
            left join tblpessoa p on (p.codpessoa = vm.codpessoafavorecido)
            where (vm.modelo || \' \' || coalesce(p.fantasia, \'\')) ilike :busca
        ';
        $bind = ['busca' => '%' . preg_replace('/\s+/', '%', trim($request->busca)) . '%'];
        if (!$inativos) {
            $sql .= ' and vm.inativo is null';
        }
        if ($request->filled('codpessoa')) {
            $sql .= ' and vm.codpessoafavorecido = :codpessoa';
            $bind['codpessoa'] = (int) $request->codpessoa;
        }
        $sql .= ' ORDER BY vm.modelo LIMIT ' . static::POR_PAGINA . ' OFFSET ' . $offset;
        return response()->json(DB::select($sql, $bind), 200);
    }

    public static function show($id)
    {
        $sql = '
            select vm.codvalemodelo, vm.modelo, vm.inativo, vm.codpessoafavorecido, p.fantasia as favorecido,
                   vm.codvalemodelo as value, vm.modelo as label
            from tblvalemodelo vm
            left join tblpessoa p on (p.codpessoa = vm.codpessoafavorecido)
            where vm.codvalemodelo = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}
