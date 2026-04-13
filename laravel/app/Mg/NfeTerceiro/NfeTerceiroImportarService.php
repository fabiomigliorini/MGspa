<?php

namespace Mg\NfeTerceiro;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pessoa\Pessoa;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Negocio\NegocioService;
use Mg\Negocio\NegocioStatus;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalDuplicatas;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloNfeTerceiro;

class NfeTerceiroImportarService
{

    /**
     * Valida se a NFe pode ser importada
     */
    public static function podeImportar(NfeTerceiro $nft): array
    {
        $erros = [];

        if (empty($nft->codnaturezaoperacao)) {
            $erros[] = 'Informe a Natureza de Operação.';
        }

        if (empty($nft->entrada)) {
            $erros[] = 'Informe a data de Entrada.';
        }

        if (empty($nft->codpessoa)) {
            $erros[] = 'Informe a Pessoa (Fornecedor).';
        }

        if (!empty($nft->codnotafiscal)) {
            $erros[] = 'Esta NFe já foi importada.';
        }

        $semProduto = $nft->NfeTerceiroItemS()->whereNull('codprodutobarra')->count();
        if ($semProduto > 0) {
            $erros[] = "Existem {$semProduto} item(ns) sem produto vinculado.";
        }

        return $erros;
    }

    /**
     * Importa a NFe de Terceiro criando NotaFiscal, Negocio, Duplicatas, Titulos e Itens
     */
    public static function importar(NfeTerceiro $nft): NfeTerceiro
    {
        $negocio = null;

        DB::beginTransaction();
        try {

            // Lock pessimista para evitar dupla importação concorrente
            $nft = NfeTerceiro::where('codnfeterceiro', $nft->codnfeterceiro)
                ->lockForUpdate()
                ->firstOrFail();

            $erros = static::podeImportar($nft);
            if (count($erros) > 0) {
                throw new Exception(implode(' ', $erros));
            }

            $natOp = NaturezaOperacao::findOrFail($nft->codnaturezaoperacao);
            $filial = Filial::findOrFail($nft->codfilial);
            $pessoa = Pessoa::findOrFail($nft->codpessoa);

            $estoqueLocal = $filial->EstoqueLocalS()->first();
            $codestoquelocal = $estoqueLocal?->codestoquelocal;

            if (empty($codestoquelocal)) {
                throw new Exception("Filial #{$filial->codfilial} não possui Estoque Local cadastrado.");
            }

            // Determina se gera Negocio (só se fornecedor não tem filiais)
            $geraNegocio = $pessoa->FilialS()->count() === 0;

            // ==================== NOTA FISCAL ====================
            $nf = new NotaFiscal();
            $nf->codoperacao = $natOp->codoperacao;
            $nf->codnaturezaoperacao = $nft->codnaturezaoperacao;
            $nf->emitida = false;
            $nf->nfechave = $nft->nfechave;
            $nf->serie = $nft->serie;
            $nf->numero = $nft->numero;
            $nf->emissao = $nft->emissao;
            $nf->saida = $nft->entrada;
            $nf->codfilial = $nft->codfilial;
            $nf->codestoquelocal = $codestoquelocal;
            $nf->codpessoa = $nft->codpessoa;
            $nf->status = 'LAN';
            $nf->modelo = NotaFiscalService::MODELO_NFE;
            $nf->valorprodutos = $nft->valorprodutos;
            $nf->valortotal = $nft->valortotal;
            $nf->valorfrete = $nft->valorfrete;
            $nf->valorseguro = $nft->valorseguro;
            $nf->valordesconto = $nft->valordesconto;
            $nf->valoroutras = $nft->valoroutras;
            $nf->icmsbase = $nft->icmsbase;
            $nf->icmsvalor = $nft->icmsvalor;
            $nf->icmsstbase = $nft->icmsstbase;
            $nf->icmsstvalor = $nft->icmsstvalor;
            $nf->ipivalor = $nft->ipivalor;
            $nf->save();

            // ==================== NEGOCIO (condicional) ====================
            $negocioFormaPagamento = null;

            if ($geraNegocio) {
                $negocio = new Negocio();
                $negocio->codoperacao = $natOp->codoperacao;
                $negocio->codnaturezaoperacao = $nft->codnaturezaoperacao;
                $negocio->codpessoa = $nft->codpessoa;
                $negocio->codfilial = $nft->codfilial;
                $negocio->codestoquelocal = $codestoquelocal;
                $negocio->lancamento = $nft->entrada;
                $negocio->codnegociostatus = NegocioStatus::ABERTO;
                $negocio->codusuario = Auth::id();
                $negocio->valorprodutos = $nft->valorprodutos;
                $negocio->valortotal = $nft->valortotal;
                $negocio->valorfrete = $nft->valorfrete;
                $negocio->valorseguro = $nft->valorseguro;
                $negocio->valordesconto = $nft->valordesconto;
                $negocio->valoroutras = $nft->valoroutras;
                $negocio->save();

                // Forma de pagamento (Boleto = 3010) se financeiro
                if ($natOp->financeiro) {
                    $negocioFormaPagamento = new NegocioFormaPagamento();
                    $negocioFormaPagamento->codnegocio = $negocio->codnegocio;
                    $negocioFormaPagamento->codformapagamento = 3010;
                    $negocioFormaPagamento->valorpagamento = $nft->valortotal;
                    $negocioFormaPagamento->save();
                }
            }

            // ==================== DUPLICATAS + TÍTULOS ====================
            $duplicatas = $nft->NfeTerceiroDuplicataS()->orderBy('dvenc')->get();
            $parcelas = $duplicatas->count();

            // Verifica se soma das duplicatas excede o total (scaling)
            $somaDuplicatas = $duplicatas->sum('vdup');
            $fatorScaling = ($somaDuplicatas > 0 && $somaDuplicatas > $nft->valortotal)
                ? $nft->valortotal / $somaDuplicatas
                : 1;

            $totalTitulos = 0;
            $i = 0;
            foreach ($duplicatas as $dup) {
                $i++;

                // NotaFiscalDuplicatas
                $nfd = new NotaFiscalDuplicatas();
                $nfd->codnotafiscal = $nf->codnotafiscal;
                $nfd->fatura = $dup->ndup;
                $nfd->vencimento = $dup->dvenc;
                $nfd->valor = round($dup->vdup * $fatorScaling, 2);
                $nfd->save();

                // Títulos (se gera negócio e é financeiro)
                if ($geraNegocio && $natOp->financeiro && $negocioFormaPagamento) {
                    $valor = $nfd->valor;

                    // Última parcela: pega diferença para evitar centavos perdidos
                    if ($i === $parcelas) {
                        $valor = $nft->valortotal - $totalTitulos;
                    }

                    $titulo = new Titulo();
                    $titulo->codnegocioformapagamento = $negocioFormaPagamento->codnegocioformapagamento;
                    $titulo->codfilial = $nft->codfilial;
                    $titulo->codtipotitulo = $natOp->codtipotitulo;
                    $titulo->codcontacontabil = $natOp->codcontacontabil;
                    $titulo->codpessoa = $nft->codpessoa;
                    $titulo->codportador = Portador::CARTEIRA;

                    if ($natOp->codoperacao == Operacao::SAIDA) {
                        $titulo->debito = $valor;
                    } else {
                        $titulo->credito = $valor;
                    }

                    $titulo->numero = 'T' . str_pad($negocio->codnegocio, 8, '0', STR_PAD_LEFT) . "-{$i}/{$parcelas}";
                    $titulo->fatura = str_pad($nft->numero, 8, '0', STR_PAD_LEFT) . "-{$i}/{$parcelas}";
                    $titulo->emissao = Carbon::now();
                    $titulo->transacao = $titulo->emissao;
                    $titulo->sistema = $titulo->emissao;
                    $titulo->vencimento = $dup->dvenc;
                    $titulo->vencimentooriginal = $dup->dvenc;
                    $titulo->gerencial = true;
                    $titulo->save();

                    // Vincula título à duplicata da NFe Terceiro
                    $dup->codtitulo = $titulo->codtitulo;
                    $dup->save();

                    // Vincula título à NFe Terceiro
                    $tnft = new TituloNfeTerceiro();
                    $tnft->codtitulo = $titulo->codtitulo;
                    $tnft->codnfeterceiro = $nft->codnfeterceiro;
                    $tnft->save();

                    $totalTitulos += $valor;
                }
            }

            // Título SLD se diferença > 0.05
            if ($geraNegocio && $natOp->financeiro && $negocioFormaPagamento && $parcelas > 0) {
                $diferenca = $nft->valortotal - $totalTitulos;
                if (abs($diferenca) > 0.05) {
                    $tituloSld = new Titulo();
                    $tituloSld->codnegocioformapagamento = $negocioFormaPagamento->codnegocioformapagamento;
                    $tituloSld->codfilial = $nft->codfilial;
                    $tituloSld->codtipotitulo = $natOp->codtipotitulo;
                    $tituloSld->codcontacontabil = $natOp->codcontacontabil;
                    $tituloSld->codpessoa = $nft->codpessoa;
                    $tituloSld->codportador = Portador::CARTEIRA;

                    if ($natOp->codoperacao == Operacao::SAIDA) {
                        $tituloSld->debito = $diferenca;
                    } else {
                        $tituloSld->credito = $diferenca;
                    }

                    $tituloSld->numero = 'T' . str_pad($negocio->codnegocio, 8, '0', STR_PAD_LEFT) . "-SLD";
                    $tituloSld->emissao = Carbon::now();
                    $tituloSld->transacao = $tituloSld->emissao;
                    $tituloSld->sistema = $tituloSld->emissao;
                    $tituloSld->vencimento = $nft->entrada;
                    $tituloSld->vencimentooriginal = $nft->entrada;
                    $tituloSld->gerencial = true;
                    $tituloSld->save();
                }
            }

            // ==================== ITENS ====================
            // Reconciliação de valores (desconto/outras não declarados)
            $valorEsperado = $nft->valorprodutos + $nft->ipivalor + $nft->icmsstvalor
                + $nft->valorfrete + $nft->valorseguro - $nft->valordesconto + $nft->valoroutras;
            $descontoRatear = 0;
            $outrasRatear = 0;
            if (abs($valorEsperado - $nft->valortotal) > 0.01) {
                if ($valorEsperado > $nft->valortotal) {
                    $descontoRatear = $valorEsperado - $nft->valortotal;
                } else {
                    $outrasRatear = $nft->valortotal - $valorEsperado;
                }
            }

            $itens = $nft->NfeTerceiroItemS()->get();
            foreach ($itens as $nti) {
                // Rateio proporcional de desconto/outras
                $descontoRateado = ($nft->valorprodutos > 0)
                    ? round(($descontoRatear / $nft->valorprodutos) * $nti->vprod, 2) : 0;
                $outrasRateado = ($nft->valorprodutos > 0)
                    ? round(($outrasRatear / $nft->valorprodutos) * $nti->vprod, 2) : 0;

                $valorItem = $nti->vprod + ($nti->ipivipi ?? 0) + ($nti->vicmsst ?? 0)
                    - ($nti->vdesc ?? 0) + ($nti->vfrete ?? 0) + ($nti->vseg ?? 0)
                    + ($nti->voutro ?? 0) + $outrasRateado - $descontoRateado;

                // NegocioProdutoBarra
                if ($geraNegocio) {
                    $npb = new NegocioProdutoBarra();
                    $npb->codnegocio = $negocio->codnegocio;
                    $npb->codprodutobarra = $nti->codprodutobarra;
                    $npb->quantidade = $nti->qcom;
                    $npb->valorprodutos = $valorItem;
                    $npb->valortotal = $valorItem;
                    $npb->valorunitario = ($nti->qcom > 0) ? round($valorItem / $nti->qcom, 6) : 0;
                    $npb->save();
                }

                // NotaFiscalProdutoBarra
                $nfpb = new NotaFiscalProdutoBarra();
                $nfpb->codnotafiscal = $nf->codnotafiscal;
                $nfpb->codprodutobarra = $nti->codprodutobarra;
                if ($geraNegocio) {
                    $nfpb->codnegocioprodutobarra = $npb->codnegocioprodutobarra;
                }
                $nfpb->quantidade = $nti->qcom;
                $nfpb->valorunitario = ($nti->qcom > 0) ? round($nti->vprod / $nti->qcom, 6) : 0;
                $nfpb->valortotal = $nti->vprod;
                $nfpb->valordesconto = ($nti->vdesc ?? 0) + $descontoRateado;
                $nfpb->valorfrete = $nti->vfrete ?? 0;
                $nfpb->valorseguro = $nti->vseg ?? 0;
                $nfpb->valoroutras = ($nti->voutro ?? 0) + $outrasRateado;

                // ICMS — redução BIT 41.17%
                $icmsBasePercentual = 1.0;
                if ($nti->ProdutoBarra?->ProdutoVariacao?->Produto?->Ncm?->bit) {
                    $icmsBasePercentual = 0.4117;
                }
                $nfpb->icmsbasepercentual = $icmsBasePercentual;
                $nfpb->icmsbase = ($nti->vbc ?? 0) * $icmsBasePercentual;
                $nfpb->icmspercentual = $nti->picms;

                // Cap ICMS 7% interestadual
                $picms = $nti->picms ?? 0;
                if ($picms > 7) {
                    $picms = 7;
                }
                $nfpb->icmsvalor = round($nfpb->icmsbase * ($picms / 100), 2);
                $nfpb->icmscst = $nti->cst;

                // ICMS ST
                $nfpb->icmsstbase = $nti->vbcst ?? 0;
                $nfpb->icmsstpercentual = $nti->picmsst ?? 0;
                $nfpb->icmsstvalor = $nti->vicmsst ?? 0;

                // IPI
                $nfpb->ipibase = $nti->ipivbc ?? 0;
                $nfpb->ipipercentual = $nti->ipipipi ?? 0;
                $nfpb->ipivalor = $nti->ipivipi ?? 0;

                $nfpb->save();
            }

            // ==================== FINALIZAÇÃO ====================
            if ($geraNegocio && $negocio) {
                $negocio->codnegociostatus = NegocioStatus::FECHADO;
                $negocio->save();
            }

            $nft->codnotafiscal = $nf->codnotafiscal;
            if ($negocio) {
                $nft->codnegocio = $negocio->codnegocio;
            }
            $nft->save();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Movimentação de estoque (via MGLara, fora da transação)
        if ($negocio) {
            NegocioService::movimentaEstoque($negocio);
        }

        return $nft->fresh();
    }
}
