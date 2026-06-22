<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Veiculo\Veiculo;

class SelectVeiculoController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);

        $qry = Veiculo::query()
            ->select(['codveiculo', 'placa', 'inativo'])
            ->orderBy('placa')
            ->orderBy('codveiculo');

        if (!$inativos) {
            $qry->ativo();
        }

        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codveiculo,
            'label' => $item->placa,
            'inativo' => $item->inativo,
        ]);
    }

    public static function show($id)
    {
        $item = Veiculo::find($id);
        if (empty($item)) {
            abort(404);
        }
        return [
            'value' => $item->codveiculo,
            'label' => $item->placa,
            'inativo' => $item->inativo,
        ];
    }
}
