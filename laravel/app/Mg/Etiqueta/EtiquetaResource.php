<?php

namespace Mg\Etiqueta;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class EtiquetaResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        $preco = $this->Produto->preco;
        $unidademedidasigla = $this->Produto->UnidadeMedida->sigla;
        $produto = $this->Produto->produto;
        $quantidadeembalagem = 1;
        if (!empty($this->codprodutoembalagem)) {
            $preco = $this->ProdutoEmbalagem->preco??$preco * $this->ProdutoEmbalagem->quantidade;
            $quantidadeembalagem = (double) $this->ProdutoEmbalagem->quantidade;
            $unidademedidasigla = $this->ProdutoEmbalagem->UnidadeMedida->sigla;
            $produto .= ' C/' . (int) $this->ProdutoEmbalagem->quantidade;
        }
        $imagem = null;
        if ($pi = $this->Produto->ProdutoImagemS()->first()) {
            $imagem = $pi->Imagem->url;
        }
        $ret = [
            'codproduto' => $this->codproduto,
            'codprodutobarra' => $this->codprodutobarra,
            'barras' => $this->barras,
            'produto' => $produto,
            'preco' => (double) $preco,
            'unidademedidasigla' => $unidademedidasigla,
            'imagem' => $imagem,
            'quantidadeembalagem' => $quantidadeembalagem,
            'quantidadeetiqueta' => $this->quantidadeetiqueta??1,
        ];
        return $ret;
    }
}
