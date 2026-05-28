<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Veiculo\VeiculoTipo;

class SelectVeiculoTipoController extends Controller
{
    public static function index(Request $request)
    {
        $qry = VeiculoTipo::select(['codveiculotipo', 'veiculotipo'])
            ->orderBy('veiculotipo');

        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codveiculotipo,
            'label' => $item->veiculotipo,
        ]);
    }
}
