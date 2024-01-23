<?php

namespace Mg\NotaFiscal;

use DB;
use Exception;
use Carbon\Carbon;

use Mg\Negocio\Negocio;
use Mg\Pessoa\PessoaService;
use Mg\Negocio\NegocioProdutoBarraService;

// use Illuminate\Support\Facades\Auth;

// use Mg\Negocio\NegocioFormaPagamento;
// use Mg\Negocio\NegocioProdutoBarra;
// use Mg\Negocio\NegocioStatus;
// use Mg\Titulo\BoletoBb\BoletoBbService;

class NotaFiscalService
{

    const MODELO_NFE = 55;
    const MODELO_NFCE = 65;

    public static function isAtiva(NotaFiscal $nota)
    {
        if (static::isAutorizada($nota)) {
            return true;
        }
        if (!$nota->emitida && !empty($nota->numero)) {
            return true;
        }
        return false;
    }

    public static function isAutorizada(NotaFiscal $nota)
    {
        return (!empty($nota->nfeautorizacao)) && (empty($nota->nfecancelamento)) && (empty($nota->nfeinutilizacao));
    }

    public static function isCanceladaInutilizada(NotaFiscal $nota)
    {
        return (!empty($nota->nfecancelamento)) || (!empty($nota->nfeinutilizacao));
    }

    // Gera nota fiscal a partir do negocio
    public static function gerarNotaFiscalDoNegocio(
        Negocio $negocio,
        $modelo = self::MODELO_NFCE,
        $gerarPagamento = true,
        NotaFiscal $nota = null
    ) {

        if ($modelo == static::MODELO_NFE && $negocio->codpessoa == PessoaService::CONSUMIDOR) {
            throw new Exception("Impossível gerar NFe para Consumidor!", 1);
        }

        if ($negocio->Pessoa->notafiscal == PessoaService::NOTAFISCAL_NUNCA) {
            throw new Exception('Pessoa marcada para Nunca Emitir NFe!', 1);
        }

        // inicia transacao no Banco
        DB::beginTransaction();

        if (empty($nota)) {
            $nota = new NotaFiscal();
            $nota->codpessoa = $negocio->codpessoa;
            if (empty($nota->codpessoa)) {
                $nota->codpessoa = Pessoa::CONSUMIDOR;
            }
            $nota->cpf = $negocio->cpf;
            $nota->codfilial = $negocio->codfilial;
            $nota->codestoquelocal = $negocio->codestoquelocal;
            $nota->serie = 1;
            $nota->numero = 0;
            $nota->modelo = $modelo;
            $nota->codnaturezaoperacao = $negocio->codnaturezaoperacao;
            $nota->emitida = $negocio->NaturezaOperacao->emitida;
            //die(date('d/m/Y'));
            $nota->emissao = Carbon::now();
            $nota->saida = $nota->emissao;

            $nota->observacoes = "";
            $nota->observacoes .= $negocio->NaturezaOperacao->mensagemprocom;

            if ($nota->modelo == NotaFiscal::MODELO_NFE && $nota->Filial->crt != Filial::CRT_SIMPLES_EXCESSO) {
                if (!empty($nota->observacoes)) {
                    $nota->observacoes .= "\n";
                }

                $nota->observacoes .= $negocio->NaturezaOperacao->observacoesnf;
            }

            $nota->frete = NotaFiscal::FRETE_SEM;
            if ($nota->modelo == NotaFiscal::MODELO_NFE) {
                if ($negocio->valorfrete > 0) {
                    $nota->frete = NotaFiscal::FRETE_EMITENTE;
                } elseif (!empty($negocio->codpessoatransportador)) {
                    $nota->frete = NotaFiscal::FRETE_DESTINATARIO;
                }
                $nota->codpessoatransportador = $negocio->codpessoatransportador;
            }
            $nota->codoperacao = $negocio->NaturezaOperacao->codoperacao;
        }

        //concatena obeservacoes
        $nota->observacoes = $nota->observacoes;
        if (!empty($nota->observacoes)) {
            $nota->observacoes .= "\n";
        }
        $nota->observacoes .= "Referente ao Negocio #{$negocio->codnegocio}";
        if (isset($negocio->PessoaVendedor)) {
            $nota->observacoes .= " - Vendedor: {$negocio->PessoaVendedor->fantasia}";
        }
        if (isset($negocio->Usuario)) {
            if (isset($negocio->Usuario->Pessoa)) {
                $nota->observacoes .= " - Caixa: {$negocio->Usuario->Pessoa->fantasia}";
            }
        }
        if (!empty($negocio->observacoes)) {
            $nota->observacoes .= " - {$negocio->observacoes}";
        }
        if (strlen($nota->observacoes) > 1500) {
            $nota->observacoes = substr($nota->observacoes, 0, 1500);
        }

        // variaveis para calcular o rateio dos juros
        $percJuros = ($negocio->valorjuros / $negocio->valorprodutos);
        $totalJuros = 0;

        // variaveis de controle do loop
        $primeiro = true;
        $chavesReferenciadas = [];

        // itens do negocio
        $itens = $negocio
            ->NegocioProdutoBarras()
            ->whereNull('inativo')
            ->orderBy('alteracao')
            ->orderBy('codnegocioprodutobarra')
            ->get();

        //percorre os itens do negocio e adiciona na nota
        foreach ($itens as $item) {

            // ignora devolvidos
            $quantidade = $item->quantidade - NegocioProdutoBarraService::quantidadeDevolvida($item);
            if ($quantidade <= 0) {
                continue;
            }

            // se o item já está em outra nota
            foreach ($item->NotaFiscalProdutoBarraS as $nfpb) {
                if (!static::isCanceladaInutilizada($nfpb->NotaFiscal)) {
                    continue (2); // vai para proximo item
                }
            }

            // Somente salvar a nota, caso exista algum produto por adicionar
            if ($primeiro) {
                $primeiro = false;
                $nota->save();
            }

            // cria registro de item
            $notaItem = new NotaFiscalProdutoBarra;
            $notaItem->codnotafiscal = $nota->codnotafiscal;
            $notaItem->codnegocioprodutobarra = $item->codnegocioprodutobarra;

            // verifica se for uma devolucao
            if (isset($item->NegocioProdutoBarraDevolucao)) {
                foreach ($item->NegocioProdutoBarraDevolucao->NotaFiscalProdutoBarras as $nfpb) {
                    if (!static::isAtiva($nfpb->NotaFiscal)) {
                        continue;
                    }
                    if (empty($nfpb->NotaFiscal->nfechave)) {
                        continue;
                    }
                    if ($nfpb->NotaFiscal->codnaturezaoperacao != $nfpb->NegocioProdutoBarra->Negocio->codnaturezaoperacao) {
                        continue;
                    }

                    $chavesReferenciadas[$nfpb->codnotafiscal] = $nfpb->NotaFiscal->nfechave;

                    // Caso a nota sendo devolvida tenha sido emitida por outra filial
                    if ($nfpb->NotaFiscal->codestoquelocal != $nota->codestoquelocal) {
                        $nota->refresh();
                        $nota->codfilial = $nfpb->NotaFiscal->codfilial;
                        $nota->codestoquelocal = $nfpb->NotaFiscal->codestoquelocal;
                        $nota->emitida = true;
                        $nota->save();
                    }
                    $notaItem->codnotafiscalprodutobarraorigem = $nfpb->codnotafiscalprodutobarra;
                }
            }

            // busca restante dos dados do negocio
            $notaItem->codprodutobarra = $item->codprodutobarra;
            $notaItem->quantidade = $quantidade;
            $notaItem->valorunitario = $item->valorunitario;

            // se quantidade nao for igual do negocio traz valores rateados
            if ($item->quantidade != $quantidade) {
                $perc = ($quantidade / $item->quantidade);
                $notaItem->valortotal = round($item->valortotal * $perc, 2);
                $notaItem->valordesconto = round($item->valordesconto * $perc, 2);
                $notaItem->valorfrete = round($item->valorfrete * $perc, 2);
                $notaItem->valoroutras = round($item->valoroutras * $perc, 2);
            } else {
                $notaItem->valortotal = $item->valortotal;
                $notaItem->valordesconto = $item->valordesconto;
                $notaItem->valorfrete = $item->valorfrete;
                $notaItem->valoroutras = $item->valoroutras;
            }

            // verifica se tem juros pra jogar no outras
            if ($percJuros > 0) {
                $juros = round($item->valortotal * $percJuros, 2);
                $notaItem->valoroutras += $juros;
                $totalJuros += $juros;
            }

            // calcula tributacao
            NotaFiscalProdutoBarraService::calcularTributacao($notaItem);

            // salva o item da nf
            $notaItem->save();

        }

        if (empty($nota->codnotafiscal)) {
            throw new Exception('Não existe nenhum produto para gerar Nota neste Negócio', 1);
        }


        // se sobrou uma diferenca no valor dos juros, joga no ultimo item da NF
        $juros = $negocio->valorjuros - $totalJuros;
        if ($juros != 0) {
            $notaItem->valoroutras += $juros;
            $notaItem->save();
        }


        foreach ($chavesReferenciadas as $cod => $chave) {
            $nfr = new NotaFiscalReferenciada();
            $nfr->codnotafiscal = $nota->codnotafiscal;
            $nfr->nfechave = $chave;
            $nfr->save();
        }



        DB::commit();
        dd($nota);


        /*





        if ($geraDuplicatas) {
            foreach ($this->NegocioFormaPagamentos as $forma) {
                foreach ($forma->Titulos as $titulo) {
                    $duplicata = new NotaFiscalDuplicatas;
                    $duplicata->codnotafiscal = $nota->codnotafiscal;
                    $duplicata->fatura = $titulo->numero;
                    $duplicata->valor = $titulo->valor;
                    $duplicata->vencimento = $titulo->vencimento;
                    if (!$duplicata->save()) {
                        $this->addErrors($duplicata->getErrors());
                        return false;
                    }
                }
            }
        }

        //retorna codigo da nota gerada
        return $nota->codnotafiscal;
        */
    }
}
