<?php

namespace Mg\Produto;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ProdutoResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['marca'] = $this->Marca->marca??null;
        $ret['Ncm'] = $this->Ncm??null;
        $ret['Cest'] = $this->Cest??null;
        $ret['secaoproduto'] = $this->SubGrupoProduto->GrupoProduto->FamiliaProduto->SecaoProduto->secaoproduto??null;
        $ret['familiaproduto'] = $this->SubGrupoProduto->GrupoProduto->FamiliaProduto->familiaproduto??null;
        $ret['grupoproduto'] = $this->SubGrupoProduto->GrupoProduto->grupoproduto??null;
        $ret['subgrupoproduto'] = $this->SubGrupoProduto->subgrupoproduto??null;

        $ret['url'] = $this->ProdutoImagem->Imagem->url??null;
        $ret['ProdutoVariacaoS'] = ProdutoVariacaoResource::collection($this->ProdutoVariacaoS);
        $ret['ProdutoImagemS'] = ProdutoImagemResource::collection($this->ProdutoImagemS()->orderBy('ordem')->get());
        return $ret;
    }
}
