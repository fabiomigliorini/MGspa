<?php

namespace Mg\Produto;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pdv\PdvCatalogoService;

/**
 * Uma barra no shape da linha da pesquisa em grade (ProdutoBarraService::pesquisa): quem recebe
 * o produto do MgInputProdutoBarras não distingue se veio do código digitado ou da pesquisa. A
 * descrição é a do item do negócio (NegocioProdutoBarraResource), com variação, unidade e
 * C/quantidade da embalagem.
 */
class ProdutoBarraPesquisaResource extends Resource
{
    public function toArray($request)
    {
        $pe = $this->ProdutoEmbalagem;
        $um = $pe ? $pe->UnidadeMedida : $this->Produto->UnidadeMedida;
        $quantidade = $pe ? floatval($pe->quantidade) : null;
        $descricao = PdvCatalogoService::montarDescricaoProduto(
            $this->Produto->produto,
            $this->ProdutoVariacao->variacao,
            $um->sigla,
            $quantidade
        );
        $preco = $pe->preco ?? $this->Produto->preco * ($quantidade ?? 1);
        $codimagem = optional($this->ProdutoVariacao->ProdutoImagem)->codimagem;

        return [
            'codprodutobarra' => $this->codprodutobarra,
            'codproduto' => $this->codproduto,
            'barras' => $this->barras,
            'descricao' => $descricao,
            'nome' => $this->Produto->produto,
            'variacao' => $this->ProdutoVariacao->variacao,
            'sigla' => $um->sigla,
            'decimais' => $um->decimais,
            'quantidade' => $quantidade,
            'preco' => floatval($preco),
            'imagem' => $codimagem
                ? config('services.mglara.imagens_url') . "/{$codimagem}.jpg"
                : null,
            'value' => $this->codprodutobarra,
            'label' => $descricao,
        ];
    }
}
