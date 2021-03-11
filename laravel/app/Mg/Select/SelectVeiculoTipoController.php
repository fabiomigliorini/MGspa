<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Mg\Veiculo\VeiculoTipo;

class SelectVeiculoTipoController extends Controller
{
    public static function index(Request $request)
    {
        // busca filiais
        $qry = VeiculoTipo::select([
            'codveiculotipo',
            'veiculotipo',
        ])->orderBy('veiculotipo');
        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }
        $ret = $qry->get();

        // renomeia colunas
        $ret = $ret->map(function($item){
            return [
                'value' => $item->codveiculotipo,
                'label' => $item->veiculotipo,
            ];
        });

        // retorna
        return $ret;
    }
}
