<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotaFiscalProdutoBarraResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscalprodutobarra' => $this->codnotafiscalprodutobarra,
            'codnotafiscal' => $this->codnotafiscal,
            'codprodutobarra' => $this->codprodutobarra,
            'codnegocioprodutobarra' => $this->codnegocioprodutobarra,
            'codnotafiscalprodutobarraorigem' => $this->codnotafiscalprodutobarraorigem,

            // Produto
            'produtoBarra' => $this->formatProdutoBarra(),

            // CFOP
            'codcfop' => $this->codcfop,
            'cfop' => $this->formatCfop(),

            // Quantidade e Valores
            'ordem' => $this->ordem,
            'quantidade' => $this->quantidade,
            'valorunitario' => $this->valorunitario,
            'valortotal' => $this->valortotal,
            'valordesconto' => $this->valordesconto,
            'valorfrete' => $this->valorfrete,
            'valorseguro' => $this->valorseguro,
            'valoroutras' => $this->valoroutras,

            // Tributos ICMS
            'csosn' => $this->csosn,
            'icmscst' => $this->icmscst,
            'icmsbase' => $this->icmsbase,
            'icmsbasepercentual' => $this->icmsbasepercentual,
            'icmspercentual' => $this->icmspercentual,
            'icmsvalor' => $this->icmsvalor,
            'icmsstbase' => $this->icmsstbase,
            'icmsstpercentual' => $this->icmsstpercentual,
            'icmsstvalor' => $this->icmsstvalor,

            // Tributos IPI
            'ipicst' => $this->ipicst,
            'ipibase' => $this->ipibase,
            'ipipercentual' => $this->ipipercentual,
            'ipivalor' => $this->ipivalor,
            'ipidevolucaovalor' => $this->ipidevolucaovalor,

            // Tributos PIS
            'piscst' => $this->piscst,
            'pisbase' => $this->pisbase,
            'pispercentual' => $this->pispercentual,
            'pisvalor' => $this->pisvalor,

            // Tributos COFINS
            'cofinscst' => $this->cofinscst,
            'cofinsbase' => $this->cofinsbase,
            'cofinspercentual' => $this->cofinspercentual,
            'cofinsvalor' => $this->cofinsvalor,

            // Outros Tributos
            'irpjbase' => $this->irpjbase,
            'irpjpercentual' => $this->irpjpercentual,
            'irpjvalor' => $this->irpjvalor,
            'csllbase' => $this->csllbase,
            'csllpercentual' => $this->csllpercentual,
            'csllvalor' => $this->csllvalor,
            'funruralpercentual' => $this->funruralpercentual,
            'funruralvalor' => $this->funruralvalor,
            'senarpercentual' => $this->senarpercentual,
            'senarvalor' => $this->senarvalor,
            'fethabkg' => $this->fethabkg,
            'fethabvalor' => $this->fethabvalor,
            'iagrokg' => $this->iagrokg,
            'iagrovalor' => $this->iagrovalor,

            // Outros
            'pedido' => $this->pedido,
            'pedidoitem' => $this->pedidoitem,
            'devolucaopercentual' => $this->devolucaopercentual,
            'descricaoalternativa' => $this->descricaoalternativa,
            'observacoes' => $this->observacoes,
            'certidaosefazmt' => $this->certidaosefazmt,

            // Relacionamentos
            'tributos' => $this->whenLoaded('NotaFiscalItemTributoS', function () {
                return $this->NotaFiscalItemTributoS;
            }),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatProdutoBarra(): ?array
    {
        if (!$this->relationLoaded('ProdutoBarra')) {
            return null;
        }

        $produtoBarra = $this->ProdutoBarra;
        if (!$produtoBarra) {
            return null;
        }

        // dd($produtoBarra->ProdutoVariacao);

        if ($imagem = $produtoBarra->ProdutoVariacao?->ProdutoImagem?->Imagem?->arquivo) {
            $imagem = "https://sistema.mgpapelaria.com.br/MGLara/public/imagens/{$imagem}";
        } else {
            $imagem = null;
        };

        return [
            'codprodutobarra' => $produtoBarra->codprodutobarra,
            'barras' => $produtoBarra->barras,
            'descricao' => $produtoBarra->descricao,
            'descricaoadicional' => $produtoBarra->descricaoadicional,
            'quantidade' => $produtoBarra->quantidade,
            'preco' => $produtoBarra->preco,
            'referencia' => $produtoBarra->referencia,
            'produto' => $produtoBarra->relationLoaded('Produto')
                ? $produtoBarra->Produto?->only(['codproduto', 'produto'])
                : null,
            'imagem' => $imagem,
            // 'unidade' => $produtoBarra->relationLoaded('Unidade')
            //     ? $produtoBarra->Unidade?->only(['codunidade', 'unidade', 'sigla'])
            //     : null,
        ];
    }

    private function formatCfop(): ?array
    {
        if (!$this->relationLoaded('Cfop')) {
            return null;
        }

        return $this->Cfop?->only(['codcfop', 'cfop', 'descricao']);
    }
}
