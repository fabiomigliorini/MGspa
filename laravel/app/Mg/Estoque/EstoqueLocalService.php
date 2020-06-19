<?php

namespace Mg\Estoque;
use Mg\MgService;

class EstoquelocalRepository extends MgService
{
    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = EstoqueLocal::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['controlaestoque'])) {
            $qry->where('controlaestoque', $filter['controlaestoque']);
        }

        if (!empty($filter['estoquelocal'])) {
            $qry->palavras('estoquelocal', $filter['estoquelocal']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar($params)
    {
        $qry = static::pesquisar($params)
                ->select('codestoquelocal', 'estoquelocal')
                ->take(10);

        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->estoquelocal,
                'value' => $item->estoquelocal,
                'id' => $item->codestoquelocal,
            ];
        }

        return $ret;
    }

    public static function lojas ()
    {
        $ret = EstoqueLocal::ativo()->where('codfilial', '!=', 199)->where('deposito', false)->orderBy('codestoquelocal')->get();
        return $ret;
    }

    public static function deposito ()
    {
        $ret = EstoqueLocal::ativo()->where('deposito', true)->orderBy('codestoquelocal')->limit(1)->get();
        return $ret[0];
    }
}
