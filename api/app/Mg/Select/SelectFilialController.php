<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Filial\Filial;

class SelectFilialController extends Controller
{
    public static function index(Request $request)
    {
        $qry = Filial::ativo()
            ->select(['codfilial', 'filial', 'nfeserie'])
            ->orderBy('codempresa')
            ->orderBy('codfilial');

        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codfilial,
            'label' => $item->filial,
            'nfeserie' => $item->nfeserie,
        ]);
    }
}
