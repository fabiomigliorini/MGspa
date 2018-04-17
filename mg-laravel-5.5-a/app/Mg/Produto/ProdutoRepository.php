<?php

namespace Mg\Produto;
use Mg\MgRepository;

class ProdutoRepository extends MgRepository
{

    public static function buscaProduto($barras, $codestoquelocal, $fiscal) {
        $pb  = static::buscaPorBarras($barras);
        $elpv = $pb->ProdutoVariacao->EstoqueLocalProdutoVariacaoS()->where('codestoquelocal', $codestoquelocal)->first();
        $es = $elpv->EstoqueSaldoS()->where('fiscal', $fiscal)->first();
        $conferencias = [];
        foreach ($es->EstoqueSaldoConferenciaS()->orderBy('data', 'DESC')->whereNull('inativo')->get() as $esc) {
            $conferencias[] = [
                'data' => $esc->data,
                'usuario' => $esc->UsuarioCriacao->usuario,
                'quantidadesistema' => $esc->quantidadesistema,
                'quantidadeinformada' => $esc->quantidadeinformada,
                'customediosistema' => $esc->customediosistema,
                'customedioinformado' => $esc->customedioinformado,
                'observacoes' => $esc->observacoes
            ];
        }
        $res = [
            'produto' => [
                'codproduto' => $pb->Produto->codproduto,
                'produto' => $pb->Produto->produto,
                'inativo' => $pb->Produto->inativo,
            ],
            'variacao' => [
                'codprodutovariacao' => $pb->ProdutoVariacao->codprodutovariacao,
                'variacao' => $pb->ProdutoVariacao->variacao,
                'descontinuado' => $pb->ProdutoVariacao->descontinuado,
                'inativo' => $pb->ProdutoVariacao->inativo,
            ],
            'localizacao' => [
                'corredor' => $elpv->corredor,
                'prateleira' => $elpv->prateleira,
                'coluna' => $elpv->coluna,
                'bloco' => $elpv->bloco,
            ],
            'vencimento' => $elpv->vencimento,
            'saldoatual' => [
                'quantidade' => $es->saldoquantidade,
                'custo' => $es->customedio
            ],
            'conferencias' => $conferencias,

        ];

        return $res;
    }

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
