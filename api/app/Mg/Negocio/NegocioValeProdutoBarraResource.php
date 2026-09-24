<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pdv\PdvService;

/**
 * Item do vale para o PDV.
 *
 * Mesmo shape do item de mercadoria (NegocioProdutoBarraResource) no que
 * a tela usa — barras, descricao montada e codimagem — porque a grade do
 * vale desenha o mesmo card. O que NAO vem, de proposito, e' desconto /
 * frete / seguro / outras: item de vale e' quantidade x preco (decisao 19).
 *
 * Isso importa para o offline: quando o PDV recarrega o negocio da API, o
 * objeto do servidor SUBSTITUI o que estava no IndexedDB. Se a descricao
 * e a imagem nao viessem aqui, a grade do vale ficaria vazia depois de um
 * F5.
 */
class NegocioValeProdutoBarraResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        $pb = $this->ProdutoBarra;
        $ret['barras'] = $pb->barras;
        $ret['codproduto'] = $pb->codproduto;

        $sigla = $pb->Produto->UnidadeMedida->sigla;
        $quantidade = null;
        if (!empty($pb->codprodutoembalagem)) {
            $sigla = $pb->ProdutoEmbalagem->UnidadeMedida->sigla;
            $quantidade = $pb->ProdutoEmbalagem->quantidade;
        }
        $ret['produto'] = PdvService::montarDescricaoProduto(
            $pb->Produto->produto,
            $pb->ProdutoVariacao->variacao,
            $sigla,
            $quantidade
        );

        $ret['codimagem'] = $pb->ProdutoVariacao->codprodutoimagem
            ? $pb->ProdutoVariacao->ProdutoImagem->codimagem
            : null;

        return $ret;
    }
}
