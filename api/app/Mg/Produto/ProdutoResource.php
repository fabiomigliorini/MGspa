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
        $ret['codsecaoproduto'] = $this->SubGrupoProduto->GrupoProduto->FamiliaProduto->codsecaoproduto??null;
        $ret['codfamiliaproduto'] = $this->SubGrupoProduto->GrupoProduto->codfamiliaproduto??null;
        $ret['codgrupoproduto'] = $this->SubGrupoProduto->codgrupoproduto??null;
        $ret['tipoproduto'] = $this->TipoProduto->tipoproduto??null;
        $ret['cest'] = $this->Cest->cest??null;
        $ret['ncm'] = $this->Ncm->ncm??null;

        $ret['unidademedida'] = $this->UnidadeMedida->sigla??null;
        $ret['tipoproduto'] = $this->TipoProduto->tipoproduto??null;
        $ret['tributacao'] = $this->Tributacao->tributacao??null;
        $ret['codestoquelocal'] = $this->codestoquelocal;
        $ret['estoquelocal'] = $this->EstoqueLocal->estoquelocal??null;

        $ret['url'] = $this->ProdutoImagem->Imagem->url??null;
        $ret['ProdutoVariacaoS'] = ProdutoVariacaoResource::collection($this->ProdutoVariacaoS);
        $ret['ProdutoEmbalagemS'] = ProdutoEmbalagemResource::collection($this->ProdutoEmbalagemS()->orderBy('quantidade')->get());
        $ret['ProdutoImagemS'] = ProdutoImagemResource::collection($this->ProdutoImagemS()->orderBy('ordem')->get());
        return $ret;
    }
}
