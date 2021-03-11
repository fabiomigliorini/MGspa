<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Mg\Cidade\Estado;

class SelectEstadoController extends Controller
{
    public static function index(Request $request)
    {
        // busca filiais
        $qry = Estado::select([
            'codestado',
            'estado',
            'sigla',
        ])->orderBy('sigla')->orderBy('codestado');
        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }
        $ret = $qry->get();

        // renomeia colunas
        $ret = $ret->map(function($item){
            return [
                'value' => $item->codestado,
                'label' => $item->estado,
            ];
        });

        // retorna
        return $ret;
    }
}
