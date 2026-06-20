<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Veiculo\VeiculoTipo;

class SelectVeiculoTipoController extends Controller
{
    public static function index(Request $request)
    {
        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);

        $qry = VeiculoTipo::select(['codveiculotipo', 'veiculotipo', 'inativo'])
            ->orderBy('veiculotipo');

        if (!$inativos) {
            $qry->ativo();
        }

        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }

        return $qry->get()->map(fn ($item) => [
            'value' => $item->codveiculotipo,
            'label' => $item->veiculotipo,
            'inativo' => $item->inativo,
        ]);
    }

    public static function show($id)
    {
        $item = VeiculoTipo::find($id);
        if (empty($item)) {
            abort(404);
        }
        return [
            'value' => $item->codveiculotipo,
            'label' => $item->veiculotipo,
            'inativo' => $item->inativo,
        ];
    }
}
