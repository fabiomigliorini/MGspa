<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Mg\Estoque\EstoqueLocal;

class SelectEstoqueLocalController extends Controller
{
    public static function index(Request $request)
    {
        // busca estoques locais
        $qry = EstoqueLocal::ativo()->select([
            'codestoquelocal',
            'estoquelocal',
            'codfilial'
        ])->orderBy('estoquelocal');

        $ret = $qry->get();

        // renomeia colunas
        $ret = $ret->map(function($item){
            return [
                'value' => $item->codestoquelocal,
                'label' => $item->estoquelocal,
                'codfilial' => $item->codfilial,
            ];
        });

        // retorna
        return $ret;
    }
}
