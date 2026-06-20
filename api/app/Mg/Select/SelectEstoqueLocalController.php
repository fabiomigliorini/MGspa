<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Estoque\EstoqueLocal;

class SelectEstoqueLocalController extends Controller
{
    public static function index(Request $request)
    {
        $qry = EstoqueLocal::ativo()
            ->select(['codestoquelocal', 'estoquelocal', 'codfilial'])
            ->orderBy('estoquelocal');

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codestoquelocal,
            'label' => $item->estoquelocal,
            'codfilial' => $item->codfilial,
        ]);
    }

    public static function show($id)
    {
        $item = EstoqueLocal::find($id);
        if (empty($item)) {
            abort(404);
        }
        return [
            'value' => $item->codestoquelocal,
            'label' => $item->estoquelocal,
            'codfilial' => $item->codfilial,
        ];
    }
}
