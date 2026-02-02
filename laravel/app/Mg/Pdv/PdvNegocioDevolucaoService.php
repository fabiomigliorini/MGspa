<?php

namespace Mg\Pdv;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioService;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\NotaFiscal\NotaFiscalStatusService;
use Mg\NotaFiscal\NotaFiscalNegocioService;
use Mg\Portador\Portador;
use Mg\Titulo\Titulo;

class PdvNegocioDevolucaoService
{
    public static function gerarDevolucao(Pdv $pdv, Negocio $negocioOriginal, $itensDevolucao)
    {

        if ($negocioOriginal->codpessoa == 1) {
            throw new Exception("informe o cadastro antes de fazer a devolução!");
        }

        $quantidadeTotal = array_sum(array_column($itensDevolucao, 'quantidadeDevolucao'));
        if (!$quantidadeTotal) {
            throw new Exception("Nenhum item para devolver!");
        }


        $negocio = new Negocio();
        $negocio->codusuario = Auth::user()->codusuario;
        $negocio->codfilial =  $negocioOriginal->codfilial;
        $negocio->codpessoa = $negocioOriginal->Pessoa->codpessoa;
        $negocio->codestoquelocal = $negocioOriginal->codestoquelocal;
        $negocio->lancamento = Carbon::now();
        $negocio->codpessoavendedor = $negocioOriginal->codpessoavendedor;
        $negocio->codnaturezaoperacao = $negocioOriginal->NaturezaOperacao->codnaturezaoperacaodevolucao;
        $negocio->codoperacao = $negocio->NaturezaOperacao->codoperacao;
        $negocio->codnegociostatus = 2;
        $negocio->codpdv = $pdv->codpdv;
        $negocio->save();
        $gerarNotaDevolucao = false;


        foreach ($itensDevolucao as $i => $itemDevolucao) {

            $quantidadedevolucao = $itemDevolucao['quantidadeDevolucao'];

            if ($quantidadedevolucao > 0) {

                //busca item a ser devolvido
                $npb_original =  NegocioProdutoBarra::findOrFail($itemDevolucao['codnegocioprodutobarra']);


                if ($npb_original === null) {
                    throw new Exception("NegocioProdutoBarra Original não localizado!", 1);
                    return false;
                }

                // soma as quantidades já existentes de devolução
                $quantDevsGeradas = 0;
                foreach ($npb_original->NegocioProdutoBarraDevolucaoS as $dev) {
                    if ($dev->Negocio->codnegociostatus == NegocioService::STATUS_FECHADO) {
                        $quantDevsGeradas += $dev->quantidade;
                    }
                }

                // verifica se a quantidade de devolução é maior que a quantidade dos produtos
                if ($quantDevsGeradas + $quantidadedevolucao > $npb_original->quantidade) {
                    throw new Exception("Devolução superior à quantidade vendida para o item com código de barras {$npb_original->ProdutoBarra->barras}!");
                }


                //cria item na devolucao
                $npb = new NegocioProdutoBarra();
                $npb->codnegocio = $negocio->codnegocio;
                $npb->quantidade = $quantidadedevolucao;
                $npb->valorunitario = $npb_original->valorunitario;

                // valor produto
                $npb->valorprodutos = round($npb->quantidade * $npb->valorunitario, 2);
                $negocio->valorprodutos += $npb->valorprodutos;

                // valor desconto
                $npb->valordesconto = round(
                    $npb->quantidade *
                        ($npb_original->valordesconto / $npb_original->quantidade),
                    2
                );
                $negocio->valordesconto += $npb->valordesconto;

                // valor frete
                $npb->valorfrete = round(
                    $npb->quantidade *
                        ($npb_original->valorfrete / $npb_original->quantidade),
                    2
                );
                $negocio->valorfrete += $npb->valorfrete;

                // valor seguro
                $npb->valorseguro = round(
                    $npb->quantidade *
                        ($npb_original->valorseguro / $npb_original->quantidade),
                    2
                );
                $negocio->valorseguro += $npb->valorseguro;

                // valor outras
                $npb->valoroutras = round(
                    $npb->quantidade *
                        ($npb_original->valoroutras / $npb_original->quantidade),
                    2
                );
                $negocio->valoroutras += $npb->valoroutras;

                // valor total
                $npb->valortotal =
                    $npb->valorprodutos
                    - $npb->valordesconto
                    + $npb->valorfrete
                    + $npb->valorseguro
                    + $npb->valoroutras;
                $negocio->valortotal += $npb->valortotal;

                // amarra ao item devolvido
                $npb->codprodutobarra = $npb_original->codprodutobarra;
                $npb->codnegocioprodutobarradevolucao = $npb_original->codnegocioprodutobarra;

                //salva item
                $npb->save();

                //Verifica quais notas fiscais referenciar na devolucao
                foreach ($npb_original->NotaFiscalProdutoBarras as $nfpb) {
                    if (NotaFiscalStatusService::isAtiva($nfpb->NotaFiscal)) {
                        $gerarNotaDevolucao = true;
                    }
                }
            }
        }

        //valor a vista/prazo
        $negocio->valoravista = 0;
        $negocio->valoraprazo = $negocio->valortotal;
        $negocio->save();

        //cria forma de pagamento
        $nfp = new NegocioFormaPagamento();
        $nfp->codnegocio = $negocio->codnegocio;
        $nfp->valorpagamento = $negocio->valoraprazo;
        $nfp->codformapagamento = 1030;
        $nfp->tipo = 90;
        $nfp->valortotal = $negocio->valortotal;
        $nfp->save();


        // Cria Registro de Titulo
        $titulo = new Titulo();
        $titulo->codnegocioformapagamento = $nfp->codnegocioformapagamento;
        $titulo->codfilial = $nfp->Negocio->codfilial;
        $titulo->codtipotitulo = $nfp->Negocio->NaturezaOperacao->codtipotitulo;
        $titulo->codcontacontabil = $nfp->Negocio->NaturezaOperacao->codcontacontabil;
        if ($nfp->Negocio->codoperacao == 2) {
            $titulo->debito = $negocio->valortotal;
        } else {
            $titulo->credito = $negocio->valortotal;
        }
        $titulo->boleto = false;
        $titulo->codpessoa = $nfp->Negocio->codpessoa;
        $titulo->numero = "N" . str_pad($nfp->codnegocio, 8, "0", STR_PAD_LEFT) . "-DEV";
        $titulo->emissao = Carbon::now();
        $titulo->transacao = $titulo->emissao;
        $titulo->sistema = $titulo->emissao;
        $vencimento = Carbon::now()->add('year', 1);
        $titulo->vencimento = $vencimento;
        $titulo->vencimentooriginal = $titulo->vencimento;
        $titulo->gerencial = true;
        $titulo->codportador = Portador::CARTEIRA;
        $titulo->save();

        // Gera a nota fiscal
        if ($gerarNotaDevolucao) {
            NotaFiscalNegocioService::gerarNotaFiscalDoNegocio($negocio, NotaFiscalService::MODELO_NFE);
        }

        // agenda movimentacao de estoque
        PdvNegocioService::movimentarEstoque($negocio);

        return $negocio;
    }
}
