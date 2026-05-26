<?php

namespace Mg\NfeTerceiro;

/**
 * Portado de /opt/www/MGsis/protected/models/NfeTerceiroItem.php (MGsis Yii):
 *  - determinaTributacao        (linha 421)
 *  - afterFind (quantidade)     (linha 458)
 *  - calculaSugestaoVenda       (linha 269)
 *  - calculaCustoICMSGarantido  (linha 307)
 *  - calculaCustoICMSApuracao   (linha 339)
 */
class NfeTerceiroItemAnaliseService
{
    const TRIBUTACAO_TRIBUTADO = 1;
    const TRIBUTACAO_ISENTO = 2;
    const TRIBUTACAO_SUBSTITUICAO = 3;
    const TRIBUTACAO_DIFERIDO = 4;

    const PERCENTUAL_ICMS_GARANTIDO = 17;

    public static function analise(NfeTerceiroItem $item): array
    {
        $item->loadMissing([
            'NfeTerceiro.Filial.Pessoa.Cidade',
            'NfeTerceiro.Pessoa.Cidade',
            'ProdutoBarra.ProdutoEmbalagem.UnidadeMedida',
            'ProdutoBarra.ProdutoVariacao.Produto.Ncm',
            'ProdutoBarra.ProdutoVariacao.Produto.Cest',
            'ProdutoBarra.ProdutoVariacao.Produto.UnidadeMedida',
            'ProdutoBarra.ProdutoVariacao.Produto.ProdutoEmbalagemS.UnidadeMedida',
        ]);

        $ctx = (object) [
            'codtributacao' => self::determinaTributacao($item),
            'quantidade' => (float) $item->qcom,
            'vicmsgarantido' => 0.0,
            'vicmscredito' => 0.0,
            'vicmsstutilizado' => 0.0,
            'mva' => null,
            'picmsbasereducao' => null,
            'picmsvenda' => null,
            'vcusto' => null,
            'vcustounitario' => null,
            'vsugestaovenda' => null,
            'vicmsvenda' => null,
            'vmargem' => null,
        ];

        $emb = $item->ProdutoBarra?->ProdutoEmbalagem;
        if ($emb) {
            $ctx->quantidade *= (float) $emb->quantidade;
        }

        self::calculaSugestaoVenda($item, $ctx);

        return [
            'codtributacao' => $ctx->codtributacao,
            'quantidade' => $ctx->quantidade,
            'embalagemBase' => $emb ? [
                'codprodutoembalagem' => $emb->codprodutoembalagem,
                'quantidade' => (float) $emb->quantidade,
                'descricao' => $emb->descricao,
                'sigla' => $emb->UnidadeMedida?->sigla,
            ] : null,
            'vicmsgarantido' => round((float) $ctx->vicmsgarantido, 2),
            'vicmscredito' => round((float) $ctx->vicmscredito, 2),
            'vicmsstutilizado' => round((float) $ctx->vicmsstutilizado, 2),
            'mva' => $ctx->mva,
            'picmsbasereducao' => $ctx->picmsbasereducao,
            'picmsvenda' => $ctx->picmsvenda,
            'vcusto' => $ctx->vcusto !== null ? round($ctx->vcusto, 2) : null,
            'vcustounitario' => $ctx->vcustounitario !== null ? round($ctx->vcustounitario, 6) : null,
            'vsugestaovenda' => $ctx->vsugestaovenda,
            'vicmsvenda' => $ctx->vicmsvenda,
            'vmargem' => $ctx->vmargem,
            'vendas' => self::montaVendas($item, $ctx),
        ];
    }

    private static function calculaSugestaoVenda(NfeTerceiroItem $item, $ctx): void
    {
        if ((float) $item->margem <= 0) {
            return;
        }
        self::calculaCustoICMSGarantido($item, $ctx);
        self::calculaCustoICMSApuracao($item, $ctx);

        $ctx->vcusto = (float) $item->vprod
            + (float) $item->ipivipi
            + (float) $ctx->vicmsstutilizado
            + (float) $item->complemento
            + (float) $ctx->vicmsgarantido
            - (float) $item->vdesc
            + (float) $item->vfrete
            + (float) $item->vseg
            + (float) $item->voutro;

        if (empty($ctx->vicmsstutilizado)) {
            $ctx->vcusto -= (float) $ctx->vicmscredito;
        }

        $ctx->vcustounitario = $ctx->vcusto;
        if (((int) $ctx->quantidade) > 0) {
            $ctx->vcustounitario /= $ctx->quantidade;
        }

        $margeminversa = 100 - (float) $item->margem;
        if (empty($ctx->vicmsstutilizado)) {
            $margeminversa -= (float) $ctx->picmsvenda * (float) $ctx->picmsbasereducao;
        }
        if ($margeminversa == 0) {
            return;
        }
        $ctx->vsugestaovenda = round($ctx->vcustounitario / ($margeminversa / 100), 6);
        $ctx->vicmsvenda = round($ctx->vsugestaovenda * (((float) $ctx->picmsvenda * (float) $ctx->picmsbasereducao) / 100), 6);
        $ctx->vmargem = round($ctx->vsugestaovenda * ((float) $item->margem / 100), 6);
    }

    private static function calculaCustoICMSGarantido(NfeTerceiroItem $item, $ctx): void
    {
        if (in_array($ctx->codtributacao, [self::TRIBUTACAO_ISENTO, self::TRIBUTACAO_DIFERIDO])) {
            return;
        }
        if (!$item->modalidadeicmsgarantido) {
            return;
        }
        $nft = $item->NfeTerceiro;
        if (!$nft || !$nft->Pessoa || !$nft->Filial) {
            return;
        }
        $codestadoFilial = $nft->Filial->Pessoa?->Cidade?->codestado;
        $codestadoPessoa = $nft->Pessoa->Cidade?->codestado;
        if ($codestadoFilial == $codestadoPessoa) {
            return;
        }
        $ctx->vicmsstutilizado = (float) $item->vicmsst;
        $base = (float) $item->vprod
            + (float) $item->ipivipi
            - (float) $item->vdesc
            + (float) $item->vfrete
            + (float) $item->vseg
            + (float) $item->voutro;
        $ctx->vicmsgarantido = $base * (self::PERCENTUAL_ICMS_GARANTIDO / 100);
        $ctx->vicmsgarantido -= (float) $ctx->vicmsstutilizado;
        if ($ctx->vicmsgarantido < 0) {
            $ctx->vicmsgarantido = 0;
        }
    }

    private static function calculaCustoICMSApuracao(NfeTerceiroItem $item, $ctx): void
    {
        // Se for garantido, cai fora, calculo é outro
        if ($item->modalidadeicmsgarantido) {
            return;
        }

        // Se tiver produto informado, utiliza tributacao do produto;
        // caso contrário, utiliza tributacao da nota de compra
        $codtributacao = $ctx->codtributacao;
        $produto = $item->ProdutoBarra?->ProdutoVariacao?->Produto;
        if (!empty($item->codprodutobarra) && $produto) {
            $codtributacao = $produto->codtributacao;
        }

        // se for isento ou diferido, não existe ICMS
        if (in_array($codtributacao, [self::TRIBUTACAO_ISENTO, self::TRIBUTACAO_DIFERIDO])) {
            return;
        }

        // verifica se é uma compra interestadual
        $interestadual = false;
        $nft = $item->NfeTerceiro;
        if ($nft && !empty($nft->codpessoa)) {
            $codestadoFilial = $nft->Filial?->Pessoa?->Cidade?->codestado;
            $codestadoPessoa = $nft->Pessoa?->Cidade?->codestado;
            if ($codestadoFilial != $codestadoPessoa) {
                $interestadual = true;
            }
        }

        $ctx->picmsvenda = 17;
        $ctx->picmsbasereducao = 1.0;
        // MVA Média das vendas 2018 e 2019
        $ctx->mva = 57.97;

        if (!empty($item->codprodutobarra) && $produto) {
            if (!empty($produto->codcest) && $produto->Cest) {
                $ctx->mva = (float) $produto->Cest->mva;
            }
            if ($produto->Ncm && !empty($produto->Ncm->bit)) {
                $ctx->picmsbasereducao = 0.4117;
            }
        }

        // Credito ICMS, no maximo 7% para compra interestadual
        if ($interestadual) {
            $maxVicmscredito = ((float) $item->vprod - (float) $item->vdesc + (float) $item->vfrete + (float) $item->vseg + (float) $item->voutro) * 0.07 * $ctx->picmsbasereducao;
            $ctx->vicmscredito = min($maxVicmscredito, (float) $item->vicms * $ctx->picmsbasereducao);
        } else {
            $ctx->vicmscredito = (float) $item->vicms * $ctx->picmsbasereducao;
        }

        // se for ICMS ST
        if ($codtributacao == self::TRIBUTACAO_SUBSTITUICAO) {
            // se ST já está destacada na nota, fim de papo
            if ((float) $item->vicmsst > 0) {
                $ctx->vicmsstutilizado = (float) $item->vicmsst;
                return;
            }
            // se nao for interestadual, considera que ICMS ST ja foi recolhida pelo fornecedor
            if (!$interestadual) {
                $ctx->picmsvenda = 0;
                return;
            }
            // calcula ST que deveria ser para quando comprado fora do estado
            $base = (float) $item->vprod
                + (float) $item->ipivipi
                - (float) $item->vdesc
                + (float) $item->vfrete
                + (float) $item->vseg
                + (float) $item->voutro;
            $base = $base * (1 + ($ctx->mva) / 100) * $ctx->picmsbasereducao;
            $ctx->vicmsstutilizado = ($base * ($ctx->picmsvenda / 100)) - $ctx->vicmscredito;
            return;
        }
    }

    private static function determinaTributacao(NfeTerceiroItem $item): int
    {
        if (!empty($item->cest)) {
            return self::TRIBUTACAO_SUBSTITUICAO;
        }
        if (!empty($item->vicmsst)) {
            return self::TRIBUTACAO_SUBSTITUICAO;
        }
        if (in_array($item->csosn, ['500'])) {
            return self::TRIBUTACAO_SUBSTITUICAO;
        }
        if (in_array($item->csosn, ['900', '400', '300'])) {
            return self::TRIBUTACAO_ISENTO;
        }
        if (!empty($item->csosn)) {
            return self::TRIBUTACAO_TRIBUTADO;
        }
        if (in_array($item->cst, ['30', '40', '41', '50', '90'])) {
            return self::TRIBUTACAO_ISENTO;
        }
        if (in_array($item->cst, ['51'])) {
            return self::TRIBUTACAO_DIFERIDO;
        }
        if (in_array($item->cst, ['10', '60', '70'])) {
            return self::TRIBUTACAO_SUBSTITUICAO;
        }
        return self::TRIBUTACAO_TRIBUTADO;
    }

    private static function montaVendas(NfeTerceiroItem $item, $ctx): array
    {
        $vendas = [];
        $produto = $item->ProdutoBarra?->ProdutoVariacao?->Produto;
        if (!$produto) {
            return $vendas;
        }

        $precoUn = (float) $produto->preco;
        $vendas[] = [
            'codprodutoembalagem' => null,
            'descricao' => null,
            'quantidade_emb' => 1.0,
            'qtd_convertida' => $ctx->quantidade,
            'sigla' => $produto->UnidadeMedida?->sigla,
            'preco_atual' => $precoUn,
            'sugestao' => $ctx->vsugestaovenda,
            'status' => self::statusVenda($precoUn, $ctx->vsugestaovenda),
        ];

        foreach ($produto->ProdutoEmbalagemS ?? [] as $emb) {
            $qtdEmb = (float) $emb->quantidade;
            $precoEmb = (float) $emb->preco;
            if ($precoEmb <= 0) {
                $precoEmb = $precoUn * $qtdEmb;
            }
            $sugestao = $ctx->vsugestaovenda !== null ? $ctx->vsugestaovenda * $qtdEmb : null;
            $vendas[] = [
                'codprodutoembalagem' => $emb->codprodutoembalagem,
                'descricao' => $emb->descricao,
                'quantidade_emb' => $qtdEmb,
                'qtd_convertida' => $qtdEmb > 0 ? $ctx->quantidade / $qtdEmb : null,
                'sigla' => $emb->UnidadeMedida?->sigla,
                'preco_atual' => $precoEmb,
                'sugestao' => $sugestao !== null ? round($sugestao, 6) : null,
                'status' => self::statusVenda($precoEmb, $sugestao),
            ];
        }

        return $vendas;
    }

    private static function statusVenda(?float $preco, ?float $sugestao): string
    {
        if ($sugestao === null || $sugestao <= 0 || $preco === null || $preco <= 0) {
            return 'neutral';
        }
        if ($preco <= $sugestao * 0.97) {
            return 'error';
        }
        if ($preco >= $sugestao * 1.05) {
            return 'warning';
        }
        return 'success';
    }
}
