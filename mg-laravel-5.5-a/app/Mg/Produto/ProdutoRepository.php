<?php

namespace Mg\Produto;
use Mg\MgRepository;

class ProdutoRepository extends MgRepository
{

    public static function buscaPorBarras($barras)
    {
        if ($pb = ProdutoBarra::where('barras', '=', $barras)->first()) {
            return $pb;
        }

        if (strlen($barras) == 6 && ($barras == (int) preg_replace('/[^0-9]/', '', $barras))) {
            if ($pb = ProdutoBarra::where('codproduto', '=', $barras)->whereNull('codprodutoembalagem')->first()) {
                return $pb;
            }
        }

        if (strstr($barras, '-')) {
            $arr = explode('-', $barras);
            if (count($arr == 2)) {
                $codigo = (int) preg_replace('/[^0-9]/', '', $arr[0]);
                $quantidade = (int) preg_replace('/[^0-9]/', '', $arr[1]);

                if ($barras == "$codigo-$quantidade") {
                    if ($pb = ProdutoBarra::where('codproduto', $codigo)->whereHas('ProdutoEmbalagem', function($query) use ($quantidade) {
                        $query->where('quantidade', $quantidade);
                    })->first())
                    return $pb;
                }
            }
        }

        return false;

    }
}
