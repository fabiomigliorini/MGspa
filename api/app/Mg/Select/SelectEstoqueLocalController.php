<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Estoque\EstoqueLocal;

class SelectEstoqueLocalController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);

        $qry = EstoqueLocal::query()
            ->select(['codestoquelocal', 'estoquelocal', 'codfilial', 'inativo'])
            ->orderBy('estoquelocal');

        if (!$inativos) {
            $qry->ativo();
        }

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codestoquelocal,
            'label' => $item->estoquelocal,
            'codfilial' => $item->codfilial,
            'inativo' => $item->inativo,
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
            'inativo' => $item->inativo,
        ];
    }
}
