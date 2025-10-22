<?php

namespace Mg\NotaFiscal;

use DB;
use Exception;
use Carbon\Carbon;

use Mg\Negocio\Negocio;
use Mg\Pessoa\PessoaService;
use Mg\Negocio\NegocioProdutoBarraService;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacaoService;
use Mg\Negocio\NegocioService;

// use Illuminate\Support\Facades\Auth;

// use Mg\Negocio\NegocioFormaPagamento;
// use Mg\Negocio\NegocioProdutoBarra;
// use Mg\Negocio\NegocioStatus;
// use Mg\Titulo\BoletoBb\BoletoBbService;

class NotaFiscalService
{
    const MODELO_NFE              = 55;
    const MODELO_NFCE             = 65;

    const FRETE_EMITENTE          = 0;
    const FRETE_DESTINATARIO      = 1;
    const FRETE_TERCEIROS         = 2;
    const FRETE_SEM               = 9;

    const TPEMIS_NORMAL           = 1; // Emissão normal (não em contingência);
    const TPEMIS_FS_IA            = 2; // Contingência FS-IA, com impressão do DANFE em formulário de segurança;
    const TPEMIS_SCAN             = 3; // Contingência SCAN (Sistema de Contingência do Ambiente Nacional) Desativação prevista para 30/06/2014;
    const TPEMIS_DPEC             = 4; // Contingência DPEC (Declaração Prévia da Emissão em Contingência);
    const TPEMIS_FS_DA            = 5; // Contingência FS-DA, com impressão do DANFE em formulário de segurança;
    const TPEMIS_SVC_AN           = 6; // Contingência SVC-AN (SEFAZ Virtual de Contingência do AN);
    const TPEMIS_SVC_RS           = 7; // Contingência SVC-RS (SEFAZ Virtual de Contingência do RS);
    const TPEMIS_OFFLINE          = 9; // Contingência off-line da NFC-e (as demais opções de contingência são válidas também para a NFC-e);

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

    public static function isDigitacao(NotaFiscal $nota)
    {
        return (empty($nota->numero) && ($nota->emitida));
    }

    public static function isCanceladaInutilizada(NotaFiscal $nota)
    {
        return (!empty($nota->nfecancelamento)) || (!empty($nota->nfeinutilizacao));
    }

    // Gera nota fiscal a partir do negocio
    public static function gerarNotaFiscalDoNegocio(
        Negocio $negocio,
        $modelo = self::MODELO_NFCE,
        $incluirPagamentos = true,
        NotaFiscal $nota = null
    ) {

        if ($modelo == static::MODELO_NFE && $negocio->codpessoa == PessoaService::CONSUMIDOR) {
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
            //die(date('d/m/Y'));
            $nota->emissao = Carbon::now();
            $nota->saida = $nota->emissao;

            $nota->observacoes = "";
            $nota->observacoes .= $negocio->NaturezaOperacao->mensagemprocom;

            if ($nota->modelo == static::MODELO_NFE && $nota->Filial->crt != Filial::CRT_SIMPLES_EXCESSO) {
                if (!empty($nota->observacoes)) {
                    $nota->observacoes .= "\n";
                }

                $nota->observacoes .= $negocio->NaturezaOperacao->observacoesnf;
            }

            $nota->frete = static::FRETE_SEM;
            if ($nota->modelo == static::MODELO_NFE) {
                if ($negocio->valorfrete > 0) {
                    $nota->frete = static::FRETE_EMITENTE;
                } elseif (!empty($negocio->codpessoatransportador)) {
                    $nota->frete = static::FRETE_DESTINATARIO;
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

    public static function excluir(NotaFiscal $nf) 
    {
        if ($nf->emitida) {
            if (!empty($nf->numero)) {
                throw new Exception("Nota Fiscal já possui atribuição de um Número. Ao invés de excluir tente Inutilizar!", 1);
            }
        }
        return $nf->delete();
    }

    public static function notasDoNegocio ($codnegocio)
    {
        // A query SQL que você forneceu
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
}
