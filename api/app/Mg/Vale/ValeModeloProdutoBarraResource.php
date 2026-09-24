<?php

namespace Mg\Vale;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pdv\PdvService;

/**
 * Mesmo shape do item de mercadoria (NegocioProdutoBarraResource): barras +
 * descricao montada. E o que o milestone 2 vai semear dentro do vale.
 */
class ValeModeloProdutoBarraResource extends Resource
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

        return $ret;
    }
}
