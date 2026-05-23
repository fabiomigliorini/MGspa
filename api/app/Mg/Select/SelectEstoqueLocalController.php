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
}
