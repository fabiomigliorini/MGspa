<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pdv\PdvService;

class NegocioProdutoBarraResource extends Resource
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
        $ret['barras'] = $this->ProdutoBarra->barras;
        $ret['codproduto'] = $this->ProdutoBarra->codproduto;
        if (empty($ret['valorprodutos'])) {
            $ret['valorprodutos'] = $ret['quantidade'] * $ret['valorunitario'];
        }
        $sigla = $this->ProdutoBarra->Produto->UnidadeMedida->sigla;
        $quantidade = null;
        if (!empty($this->ProdutoBarra->codprodutoembalagem)) {
            $sigla = $this->ProdutoBarra->ProdutoEmbalagem->UnidadeMedida->sigla;
            $quantidade = $this->ProdutoBarra->ProdutoEmbalagem->quantidade;
        }
        $ret['produto'] = PdvService::montarDescricaoProduto(
            $this->ProdutoBarra->Produto->produto,
            $this->ProdutoBarra->ProdutoVariacao->variacao,
            $sigla,
            $quantidade
        );
        $ret['codimagem'] = ($this->ProdutoBarra->ProdutoVariacao->codprodutoimagem) ? $this->ProdutoBarra->ProdutoVariacao->ProdutoImagem->codimagem : null;

        // Todas as Devolucoes ativas do iten
        $devs = $this->NegocioProdutoBarraDevolucaoS()->with('Negocio')->whereHas('Negocio', function ($qry) {
            $qry->where('tblnegocio.codnegociostatus', 2);
        })->get();
        $ret['devolucoes'] = [];
        foreach ($devs as $dev) {
            $ret['devolucoes'][] = [
                'codnegocioprodutobarra' => $dev->codnegocioprodutobarra,
                'codnegocio' => $dev->codnegocio,
                'quantidade' => $dev->quantidade,
                'lancamento' => $dev->Negocio->lancamento,
                'codnegociostatus' => $dev->Negocio->codnegociostatus,
            ];
        }

        if ($this->codnegocioprodutobarradevolucao) {
            $ret['devolucao'] = [
                'codnegocio' => $this->NegocioProdutoBarraDevolucao->codnegocio,
                'lancamento' => $this->NegocioProdutoBarraDevolucao->Negocio->lancamento,
            ];
        }

        return $ret;
    }
}
