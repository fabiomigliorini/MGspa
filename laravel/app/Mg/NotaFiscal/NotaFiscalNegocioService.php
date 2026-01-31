<?php

namespace Mg\NotaFiscal;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Mg\Negocio\Negocio;
use Mg\Pessoa\PessoaService;
use Mg\Negocio\NegocioProdutoBarraService;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacaoService;
use Mg\Negocio\NegocioService;
use Mg\Tributacao\TributacaoService;
use Mg\Pessoa\Pessoa;

class NotaFiscalNegocioService
{
    /**
     * Retorna as notas fiscais vinculadas a um negócio
     */
    public static function notasDoNegocio($codnegocio)
    {
        $sql = "
            select distinct nf.*
            from tblnegocioprodutobarra npb
            inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnegocioprodutobarra = npb.codnegocioprodutobarra)
            inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal)
            where npb.codnegocio = :codnegocio
        ";
        $nfs = NotaFiscal::fromQuery($sql, ['codnegocio' => $codnegocio]);
        return $nfs;
    }

    /**
     * Gera nota fiscal a partir do negocio
     */
    public static function gerarNotaFiscalDoNegocio(
        Negocio $negocio,
        $modelo = NotaFiscalService::MODELO_NFCE,
        $incluirPagamentos = true,
        ?NotaFiscal $nota = null
    ) {

        if ($modelo == NotaFiscalService::MODELO_NFE && $negocio->codpessoa == PessoaService::CONSUMIDOR) {
            throw new Exception("Impossível gerar NFe para Consumidor!", 1);
        }

        if ($negocio->Pessoa->notafiscal == PessoaService::NOTAFISCAL_NUNCA) {
            throw new Exception('Pessoa marcada para Nunca Emitir NFe!', 1);
        }

        if ($negocio->codnegociostatus != NegocioService::STATUS_FECHADO) {
            throw new Exception('Negócio cancelado ou aberto, impossível gerar a NFE!', 1);
        }

        // inicia transacao no Banco
        DB::beginTransaction();

        if (empty($nota)) {
            $nota = new NotaFiscal;
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
            $nota->emissao = Carbon::now();
            $nota->saida = $nota->emissao;

            $nota->observacoes = "";
            $nota->observacoes .= $negocio->NaturezaOperacao->mensagemprocom;

            if ($nota->modelo == NotaFiscalService::MODELO_NFE && $nota->Filial->crt != Filial::CRT_SIMPLES_EXCESSO) {
                if (!empty($nota->observacoes)) {
                    $nota->observacoes .= "\n";
                }

                $nota->observacoes .= $negocio->NaturezaOperacao->observacoesnf;
            }

            $nota->frete = NotaFiscalService::FRETE_SEM;
            if ($nota->modelo == NotaFiscalService::MODELO_NFE) {
                if ($negocio->valorfrete > 0) {
                    $nota->frete = NotaFiscalService::FRETE_EMITENTE;
                } elseif (!empty($negocio->codpessoatransportador)) {
                    $nota->frete = NotaFiscalService::FRETE_DESTINATARIO;
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
            ->orderBy('ordenacao', 'desc')
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
                if (!NotaFiscalStatusService::isCanceladaInutilizada($nfpb->NotaFiscal)) {
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
                    if (!NotaFiscalStatusService::isAtiva($nfpb->NotaFiscal)) {
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

            if ($negocio->NaturezaOperacao->preco == NaturezaOperacaoService::PRECO_TRANSFERENCIA) {
                $notaItem->valorunitario = round($item->valorunitario * 0.7, 2);
                $notaItem->valortotal = $quantidade * $notaItem->valorunitario;
            } else {
                $notaItem->valorunitario = $item->valorunitario;

                // se quantidade nao for igual do negocio traz valores rateados
                if ($item->quantidade != $quantidade) {
                    $perc = ($quantidade / $item->quantidade);
                    if (!empty($item->Negocio->codpdv)) {
                        $notaItem->valortotal = round($item->valorprodutos * $perc, 2);
                    } else {
                        $notaItem->valortotal = round($item->valortotal * $perc, 2);
                    }
                    $notaItem->valordesconto = round($item->valordesconto * $perc, 2);
                    $notaItem->valorfrete = round($item->valorfrete * $perc, 2);
                    $notaItem->valorseguro = round($item->valorseguro * $perc, 2);
                    $notaItem->valoroutras = round($item->valoroutras * $perc, 2);
                } else {
                    if (!empty($item->Negocio->codpdv)) {
                        $notaItem->valortotal = $item->valorprodutos;
                    } else {
                        $notaItem->valortotal = $item->valortotal;
                    }
                    $notaItem->valordesconto = $item->valordesconto;
                    $notaItem->valorfrete = $item->valorfrete;
                    $notaItem->valorseguro = $item->valorseguro;
                    $notaItem->valoroutras = $item->valoroutras;
                }

                // verifica se tem juros pra jogar no outras
                if ($percJuros > 0) {
                    $juros = round($item->valortotal * $percJuros, 2);
                    $notaItem->valoroutras += $juros;
                    $totalJuros += $juros;
                }
            }

            // calcula tributacao
            NotaFiscalProdutoBarraService::calcularTributacao($notaItem);

            // salva o item da nf
            $notaItem->save();

            // Reforma Tributaria
            TributacaoService::recalcularTributosItem($notaItem);
        }

        if (empty($nota->codnotafiscal)) {
            throw new Exception('Não existe nenhum produto para gerar Nota neste Negócio', 1);
        }

        // se sobrou uma diferenca no valor dos juros, joga no ultimo item da NF
        if ($negocio->NaturezaOperacao->preco != NaturezaOperacaoService::PRECO_TRANSFERENCIA) {
            $juros = $negocio->valorjuros - $totalJuros;
            if ($juros != 0) {
                $notaItem->valoroutras += $juros;
                $notaItem->save();
            }
        }

        // adiciona as chaves de nfes referenciadas
        foreach ($chavesReferenciadas as $cod => $chave) {
            $nfr = new NotaFiscalReferenciada([
                'codnotafiscal' => $nota->codnotafiscal,
                'nfechave' => $chave,
            ]);
            $nfr->save();
        }

        // se nao for pra incluir os pagamentos finaliza aqui
        if (!$incluirPagamentos) {
            DB::commit();
            return $nota;
        }

        // adiciona as duplicatas
        foreach ($negocio->NegocioFormaPagamentos as $forma) {
            $pag = new NotaFiscalPagamento([
                'codnotafiscal' => $nota->codnotafiscal,
                'avista' => $forma->avista,
                'tipo' => $forma->tipo,
                'valorpagamento' => $forma->valortotal,
                'troco' => $forma->valortroco,
                'integracao' => $forma->integracao,
                'codpessoa' => $forma->codpessoa,
                'bandeira' => $forma->bandeira,
                'autorizacao' => $forma->autorizacao,
            ]);
            if ($pag->tipo == 99) {
                $pag->descricao = $forma->FormaPagamento->formapagamento;
            }
            $pag->save();
            foreach ($forma->Titulos as $titulo) {
                $duplicata = new NotaFiscalDuplicatas([
                    'codnotafiscal' => $nota->codnotafiscal,
                    'fatura' => $titulo->numero,
                    'valor' => abs($titulo->credito + $titulo->debito),
                    'vencimento' => $titulo->vencimento,
                ]);
                $duplicata->save();
            }
        }

        // salva no Banco e retorna
        DB::commit();
        return $nota;
    }
}
