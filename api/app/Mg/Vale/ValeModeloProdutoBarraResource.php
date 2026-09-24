<?php

namespace Mg\Vale;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pdv\PdvService;

/**
 * Mesmo shape do item de mercadoria (NegocioProdutoBarraResource): barras,
 * descricao montada e imagem. E o que o milestone 2 vai semear dentro do
 * vale.
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

        // URL pronta, igual a que o select de produto devolve, para a tela
        // nao ter que montar caminho de imagem em lugar nenhum.
        $codimagem = optional($pb->ProdutoVariacao->ProdutoImagem)->codimagem;
        $ret['imagem'] = $codimagem
            ? config('services.mglara.imagens_url') . "/{$codimagem}.jpg"
            : null;

        return $ret;
    }
}
