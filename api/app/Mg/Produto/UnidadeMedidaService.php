<?php

namespace Mg\Produto;

use Mg\MgService;

class UnidadeMedidaService extends MgService
{
    /**
     * Fração da quantidade pela unidade em que a barra é vendida (TASK-53), a mesma regra do
     * negocios/src/utils/quantidade.js: a mensagem de recusa, ou null quando a quantidade cabe
     * nas casas decimais da unidade (0 é só inteiro, 2 aceita 0,53 MT, 3 aceita 0,712 KG). A
     * unidade é a da embalagem, ou senão a do produto, como a sigla da descrição.
     */
    public static function fracaoRecusada(ProdutoBarra $pb, $quantidade): ?string
    {
        $um = $pb->codprodutoembalagem
            ? $pb->ProdutoEmbalagem->UnidadeMedida
            : $pb->Produto->UnidadeMedida;
        $quantidade = (float) $quantidade;
        if (abs($quantidade - round($quantidade, $um->decimais)) < 0.0000001) {
            return null;
        }
        $qtd = rtrim(rtrim(number_format($quantidade, 3, ',', '.'), '0'), ',');
        $regra = $um->decimais == 0
            ? "{$um->sigla} não aceita fração"
            : "{$um->sigla} aceita só {$um->decimais} " . ($um->decimais == 1 ? 'casa' : 'casas') . ' depois da vírgula';
        return "{$pb->Produto->produto} ({$qtd} {$um->sigla}): {$regra}!";
    }

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = UnidadeMedida::query();

        if (!empty($filter['codunidademedida'])) {
            $qry->where('codunidademedida', $filter['codunidademedida']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['unidademedida'])) {
            $qry->palavras('unidademedida', $filter['unidademedida']);
        }

        if (!empty($filter['sigla'])) {
            $qry->where('sigla', 'ilike', "%{$filter['sigla']}%");
        }

        if (empty($sort)) {
            $qry->orderBy('unidademedida');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codunidademedida', 'unidademedida', 'sigla')
            ->take(20);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => "{$item->unidademedida} ({$item->sigla})",
                'value' => $item->codunidademedida,
                'id' => $item->codunidademedida,
                'sigla' => $item->sigla,
            ];
        }
        return $ret;
    }
}
