<?php
namespace Mg\NaturezaOperacao;
use Mg\MgRepository;

class NaturezaOperacaoRepository extends MgRepository
{
    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = NaturezaOperacao::query();
        // if (!empty($filter['inativo'])) {
        //     $qry->AtivoInativo($filter['inativo']);
        // }
        //
        // if (!empty($filter['estoquelocal'])) {
        //     $qry->palavras('estoquelocal', $filter['estoquelocal']);
        // }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar($params)
    {
        $qry = static::pesquisar($params)
                ->select('naturezaoperacao', 'codoperacao')
                ->take(50);

        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->naturezaoperacao,
                'value' => $item->naturezaoperacao,
                'id' => $item->codoperacao,
            ];
        }


        return $ret;
    }
}
