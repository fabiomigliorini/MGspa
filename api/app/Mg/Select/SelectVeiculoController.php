<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Veiculo\Veiculo;

class SelectVeiculoController extends Controller
{
    public static function index(Request $request)
    {
        $qry = Veiculo::ativo()
            ->select(['codveiculo', 'placa'])
            ->orderBy('placa')
            ->orderBy('codveiculo');

        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codveiculo,
            'label' => $item->placa,
        ]);
    }
}
