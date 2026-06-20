<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Filial\Filial;

class SelectFilialController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);

        $qry = Filial::query()
            ->select(['codfilial', 'filial', 'nfeserie', 'funruralvenda', 'inativo'])
            ->orderBy('codempresa')
            ->orderBy('codfilial');

        if (!$inativos) {
            $qry->ativo();
        }

        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codfilial,
            'label' => $item->filial,
            'nfeserie' => $item->nfeserie,
            'funruralvenda' => (bool) $item->funruralvenda,
            'inativo' => $item->inativo,
        ]);
    }

    public static function show($id)
    {
        $item = Filial::find($id);
        if (empty($item)) {
            abort(404);
        }
        return [
            'value' => $item->codfilial,
            'label' => $item->filial,
            'nfeserie' => $item->nfeserie,
            'funruralvenda' => (bool) $item->funruralvenda,
            'inativo' => $item->inativo,
        ];
    }
}
