<?php

namespace Mg\Vale;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pdv\PdvCatalogoService;

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

        $um = $pb->Produto->UnidadeMedida;
        $quantidade = null;
        if (!empty($pb->codprodutoembalagem)) {
            $um = $pb->ProdutoEmbalagem->UnidadeMedida;
            $quantidade = $pb->ProdutoEmbalagem->quantidade;
        }
        $ret['produto'] = PdvCatalogoService::montarDescricaoProduto(
            $pb->Produto->produto,
            $pb->ProdutoVariacao->variacao,
            $um->sigla,
            $quantidade
        );
        // a quantidade do kit respeita as casas da unidade (TASK-53)
        $ret['sigla'] = $um->sigla;
        $ret['decimais'] = $um->decimais;

        // URL pronta, igual a que o select de produto devolve, para a tela
        // nao ter que montar caminho de imagem em lugar nenhum.
        $codimagem = optional($pb->ProdutoVariacao->ProdutoImagem)->codimagem;
        $ret['imagem'] = $codimagem
            ? config('services.mglara.imagens_url') . "/{$codimagem}.jpg"
            : null;

        return $ret;
    }
}
