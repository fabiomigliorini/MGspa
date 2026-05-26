<?php

namespace Mg\NfeTerceiro\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NfeTerceiroItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnfeterceiroitem' => $this->codnfeterceiroitem,
            'codnfeterceiro' => $this->codnfeterceiro,
            'nitem' => $this->nitem,

            // Produto
            'cprod' => $this->cprod,
            'xprod' => $this->xprod,
            'cean' => $this->cean,
            'ceantrib' => $this->ceantrib,
            'ncm' => $this->ncm,
            'cest' => $this->cest,
            'cfop' => $this->cfop,

            // Quantidades
            'qcom' => $this->qcom,
            'ucom' => $this->ucom,
            'qtrib' => $this->qtrib,
            'utrib' => $this->utrib,

            // Valores
            'vuncom' => $this->vuncom,
            'vuntrib' => $this->vuntrib,
            'vprod' => $this->vprod,
            'vfrete' => $this->vfrete,
            'vseg' => $this->vseg,
            'vdesc' => $this->vdesc,
            'voutro' => $this->voutro,

            // ICMS
            'orig' => $this->orig,
            'cst' => $this->cst,
            'csosn' => $this->csosn,
            'modbc' => $this->modbc,
            'vbc' => $this->vbc,
            'picms' => $this->picms,
            'vicms' => $this->vicms,
            'predbc' => $this->predbc,

            // ICMS ST
            'modbcst' => $this->modbcst,
            'vbcst' => $this->vbcst,
            'picmsst' => $this->picmsst,
            'vicmsst' => $this->vicmsst,
            'pmvast' => $this->pmvast,
            'predbcst' => $this->predbcst,

            // IPI
            'ipicst' => $this->ipicst,
            'ipivbc' => $this->ipivbc,
            'ipipipi' => $this->ipipipi,
            'ipivipi' => $this->ipivipi,

            // PIS
            'piscst' => $this->piscst,
            'pisvbc' => $this->pisvbc,
            'pisppis' => $this->pisppis,
            'pisvpis' => $this->pisvpis,

            // COFINS
            'cofinscst' => $this->cofinscst,
            'cofinsvbc' => $this->cofinsvbc,
            'cofinspcofins' => $this->cofinspcofins,
            'cofinsvcofins' => $this->cofinsvcofins,

            // Outros
            'compoetotal' => $this->compoetotal,
            'infadprod' => $this->infadprod,
            'modalidadeicmsgarantido' => $this->modalidadeicmsgarantido,

            // Vinculo produto interno
            'codprodutobarra' => $this->codprodutobarra,
            'produtoBarra' => $this->formatProdutoBarra(),
            'complemento' => $this->complemento,
            'margem' => $this->margem,
            'observacoes' => $this->observacoes,

            // Conferência
            'conferencia' => $this->conferencia,
            'codusuarioconferencia' => $this->codusuarioconferencia,
            'usuarioConferencia' => $this->relationLoaded('UsuarioConferencia')
                ? $this->UsuarioConferencia?->only(['codusuario', 'usuario'])
                : null,
        ];
    }

    private function formatProdutoBarra(): ?array
    {
        if (!$this->relationLoaded('ProdutoBarra')) {
            return null;
        }
        $pb = $this->ProdutoBarra;
        if (!$pb) {
            return null;
        }
        return [
            'codprodutobarra' => $pb->codprodutobarra,
            'barras' => $pb->barras,
            'codprodutoembalagem' => $pb->codprodutoembalagem,
            'produto' => $this->formatProduto($pb->ProdutoVariacao?->Produto),
            'variacao' => $this->formatVariacao($pb->ProdutoVariacao),
        ];
    }

    private function formatProduto($p): ?array
    {
        if (!$p) {
            return null;
        }
        return [
            'codproduto' => $p->codproduto,
            'produto' => $p->produto,
            'inativo' => $p->inativo,
            'preco' => $p->preco,
            'abc' => $p->abc,
            'abccategoria' => $p->abccategoria,
            'abcposicao' => $p->abcposicao,
            'codtributacao' => $p->codtributacao,
            'importado' => $p->importado,
            'codncm' => $p->codncm,
            'codcest' => $p->codcest,
            'ncm' => $p->relationLoaded('Ncm') ? $p->Ncm?->only(['codncm', 'ncm', 'bit']) : null,
            'cest' => $p->relationLoaded('Cest') ? $p->Cest?->only(['codcest', 'cest', 'mva']) : null,
            'tributacao' => $p->relationLoaded('Tributacao')
                ? $p->Tributacao?->only(['codtributacao', 'tributacao'])
                : null,
            'produtoEmbalagens' => $p->relationLoaded('ProdutoEmbalagemS')
                ? $p->ProdutoEmbalagemS->map(fn ($e) => [
                    'codprodutoembalagem' => $e->codprodutoembalagem,
                    'descricao' => $e->descricao,
                    'quantidade' => $e->quantidade,
                    'preco' => $e->preco,
                    'unidadeMedida' => $e->relationLoaded('UnidadeMedida')
                        ? $e->UnidadeMedida?->only(['codunidademedida', 'sigla'])
                        : null,
                ])->values()->all()
                : null,
        ];
    }

    private function formatVariacao($v): ?array
    {
        if (!$v) {
            return null;
        }
        return [
            'codprodutovariacao' => $v->codprodutovariacao,
            'variacao' => $v->variacao,
            'produtoBarras' => $v->relationLoaded('ProdutoBarraS')
                ? $v->ProdutoBarraS->map(fn ($b) => [
                    'codprodutobarra' => $b->codprodutobarra,
                    'barras' => $b->barras,
                    'codprodutoembalagem' => $b->codprodutoembalagem,
                ])->values()->all()
                : null,
        ];
    }
}
