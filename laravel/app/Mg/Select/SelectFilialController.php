<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Mg\Filial\Filial;

class SelectFilialController extends Controller
{
    public static function index(Request $request)
    {
        // busca filiais
        $qry = Filial::ativo()->select([
            'codfilial',
            'filial',
            'nfeserie',
        ])->orderBy('codempresa')->orderBy('codfilial');
        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }
        $ret = $qry->get();

        // renomeia colunas
        $ret = $ret->map(function($item){
            return [
                'value' => $item->codfilial,
                'label' => $item->filial,
                'nfeserie' => $item->nfeserie,
            ];
        });

        // retorna
        return $ret;
    }
}
