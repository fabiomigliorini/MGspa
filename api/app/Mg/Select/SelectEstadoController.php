<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Cidade\Estado;

class SelectEstadoController extends Controller
{
    public static function index(Request $request)
    {
        $qry = Estado::select(['codestado', 'estado', 'sigla'])
            ->orderBy('sigla')
            ->orderBy('codestado');

        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codestado,
            'label' => $item->estado,
            'sigla' => $item->sigla,
        ]);
    }

    public static function show($id)
    {
        $item = Estado::find($id);
        if (empty($item)) {
            abort(404);
        }
        return [
            'value' => $item->codestado,
            'label' => $item->estado,
            'sigla' => $item->sigla,
        ];
    }
}
