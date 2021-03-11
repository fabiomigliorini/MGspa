<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Mg\Veiculo\Veiculo;

class SelectVeiculoController extends Controller
{
    public static function index(Request $request)
    {
        // busca filiais
        $qry = Veiculo::ativo()->select([
            'codveiculo',
            'placa',
        ])->orderBy('placa')->orderBy('codveiculo');
        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }
        $ret = $qry->get();

        // renomeia colunas
        $ret = $ret->map(function($item){
            return [
                'value' => $item->codveiculo,
                'label' => $item->placa,
            ];
        });

        // retorna
        return $ret;
    }
}
