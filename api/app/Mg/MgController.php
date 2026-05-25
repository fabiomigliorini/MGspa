<?php

namespace Mg;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Base controller do domínio MG.
 *
 * Por enquanto só expõe o helper `filtros()` (parse de sort/fields/page +
 * filtros restantes), porta literal do MgController legado.
 */
class MgController extends Controller
{
    public function filtros(Request $request)
    {
        $sort = $request->sort;
        if (!empty($sort)) {
            $sort = explode(',', $sort);
        }

        $fields = $request->fields;
        if (!empty($fields)) {
            $fields = explode(',', $fields);
        }

        $filter = $request->all();
        unset($filter['fields'], $filter['sort'], $filter['page']);

        return [$filter, $sort, $fields];
    }
}
