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
    /** tPag 12 = Vale Presente, que é como o vale compras entra na nota. */
    const TPAG_VALE_PRESENTE = 12;

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
        ?NotaFiscal $nota = null,
        bool $ignorarJaNotados = false
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

        // VALE COMPRAS -- a venda do vale e' recebimento antecipado, sem fato
        // gerador de ICMS: ela NAO entra na nota, e o documento fiscal sai
        // na retirada do material, com tPag 12 (tese fiscal, doc-1).
        //
        // Daqui para baixo TUDO que trata disso esta' atras de $temVale.
        // Negocio sem vale -- que e' a esmagadora maioria das notas da
        // empresa -- segue pelo caminho de sempre, linha por linha.
        $temVale = $negocio->NegocioValeS()->whereNull('inativo')->exists();

        if ($temVale && $negocio->NegocioProdutoBarraS()->whereNull('inativo')->count() == 0) {
            throw new Exception('Este negócio é só de Vale Compras e não gera Nota Fiscal. A venda do vale é recebimento antecipado, sem fato gerador de ICMS: a nota sai na retirada do material.', 1);
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

        // O juros do parcelamento JA' vem rateado item a item, gravado em
        // tblnegocioprodutobarra.valorjuros pelo PDV. Aqui ele so' e' somado
        // ao "outras" do item da nota -- a NF-e nao tem campo de juros no
        // item, ele tem que virar vOutro.
        //
        // A fatia que coube ao VALE nao entra na nota por construcao: ela
        // esta' em tblnegociovale.valorjuros, e o loop abaixo so' percorre
        // itens de mercadoria.

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

            // se o item já está em outra nota (a menos que seja explicitamente pra incluir todos)
            if (!$ignorarJaNotados) {
                foreach ($item->NotaFiscalProdutoBarraS as $nfpb) {
                    if (!NotaFiscalStatusService::isCanceladaInutilizada($nfpb->NotaFiscal)) {
                        continue (2); // vai para proximo item
                    }
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

                // juros do parcelamento: ja' rateado no negocio, vira
                // "outras" no item da nota
                $notaItem->valoroutras += $item->valorjuros ?? 0;
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

        // (nao ha' sobra de arredondamento para corrigir aqui: o rateio do
        // juros acontece uma vez so', no negocio, e a sobra ja' foi para o
        // ultimo item la')

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

        // Consumo de vale: N pagamentos no banco viram UM detPag tPag 12 na
        // nota (decisão 6 do plano). Só entra quando existe mais de um --
        // com zero ou um vale consumido, nada muda.
        $valesConsumidos = $negocio->NegocioFormaPagamentoS()
            ->where('tipo', static::TPAG_VALE_PRESENTE)
            ->orderBy('codnegocioformapagamento')
            ->get();
        $agruparVale = $valesConsumidos->count() > 1;

        // adiciona as duplicatas
        if (!$temVale) {
            foreach ($negocio->NegocioFormaPagamentos as $forma) {
                // os tPag 12 saem juntos, numa linha só, depois do laço
                if ($agruparVale && $forma->tipo == static::TPAG_VALE_PRESENTE) {
                    continue;
                }
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
            if ($agruparVale) {
                $valor = 0;
                foreach ($valesConsumidos as $forma) {
                    $valor += (float) $forma->valortotal;
                }
                static::detPagValeAgrupado($nota, round($valor, 2), $valesConsumidos->first()->avista);
            }
        } else {
            static::pagamentosComVale($nota, $negocio, $agruparVale);
        }

        // salva no Banco e retorna
        DB::commit();
        return $nota;
    }

    /**
     * Os N vales consumidos viram UM detPag tPag 12.
     *
     * No banco os pagamentos continuam N, um por título -- é assim que o
     * `baixarVales` amortiza cada crédito e que o cancelamento sabe o que
     * estornar de cada um. O que a nota precisa declarar é só "o cliente
     * pagou R$ X em vale presente": a SEFAZ não tem onde guardar qual vale
     * foi, e N linhas iguais só poluem o cupom.
     */
    public static function detPagValeAgrupado(NotaFiscal $nota, $valor, $avista = true)
    {
        $pag = new NotaFiscalPagamento([
            'codnotafiscal' => $nota->codnotafiscal,
            'avista' => $avista,
            'tipo' => static::TPAG_VALE_PRESENTE,
            'valorpagamento' => round($valor, 2),
            'troco' => null,
            'integracao' => false,
            'codpessoa' => null,
            'bandeira' => null,
            'autorizacao' => null,
        ]);
        $pag->save();
        return $pag;
    }

    /**
     * Pagamentos e duplicatas de um negocio que TEM vale compras.
     *
     * O negocio cobrou do cliente mercadoria + vale; a nota so' pode
     * declarar a mercadoria. A diferenca entre os dois -- a fatia paga do
     * vale, mais a fatia de juros dele -- precisa sair dos pagamentos, ou a
     * SEFAZ rejeita a nota (a soma dos vPag menos o vTroco tem que dar
     * exatamente o vNF).
     *
     * De QUAL pagamento tirar importa para a conciliacao: o que a adquirente
     * e a DIMP enxergam e' o valor REAL passado no cartao e no PIX, com o
     * cAut de verdade. Tirar do dinheiro primeiro deixa cartao e PIX
     * intactos sempre que da', e so' encosta neles quando o dinheiro nao
     * cobre o vale. Por isso a ordem e' dinheiro -> PIX -> o resto.
     *
     * O corte de cada pagamento para no seu valor LIQUIDO
     * (valortotal - valortroco): o troco e' dinheiro que voltou para a mao
     * do cliente e nao pagou nada, nem mercadoria nem vale.
     */
    public static function pagamentosComVale(NotaFiscal $nota, Negocio $negocio, $agruparVale = false)
    {
        // o valortotal da nota e' somado pelo banco a partir dos itens que
        // acabaram de ser gravados: sem o refresh, o objeto em memoria ainda
        // esta' zerado
        $nota->refresh();

        $aConsumir = round($negocio->valortotal - $nota->valortotal, 2);

        // dinheiro (01) -> PIX (17) -> o resto, e dentro de cada grupo na
        // ordem de lancamento, para o resultado nao depender do Postgres
        $formas = $negocio->NegocioFormaPagamentoS()
            ->orderByRaw('case tipo when 1 then 1 when 17 then 2 else 3 end')
            ->orderBy('codnegocioformapagamento')
            ->get();

        $pagamentos = [];
        $valeAgrupado = null;
        $valeAgrupadoAvista = true;
        foreach ($formas as $forma) {
            $troco = round((float) $forma->valortroco, 2);
            $liquido = round((float) $forma->valortotal - $troco, 2);
            $corte = round(min(max($liquido, 0), max($aConsumir, 0)), 2);
            $aConsumir = round($aConsumir - $corte, 2);
            $valor = round((float) $forma->valortotal - $corte, 2);

            // pagamento que foi inteiro para o vale nao tem o que declarar
            if ($valor <= 0) {
                continue;
            }

            // Os N tPag 12 viram UMA linha só: acumula aqui e grava depois
            // do laço, ainda antes do acerto da diferença.
            if ($agruparVale && $forma->tipo == static::TPAG_VALE_PRESENTE) {
                $valeAgrupado = round(($valeAgrupado ?? 0) + $valor, 2);
                $valeAgrupadoAvista = $forma->avista;
                continue;
            }

            $pag = new NotaFiscalPagamento([
                'codnotafiscal' => $nota->codnotafiscal,
                'avista' => $forma->avista,
                'tipo' => $forma->tipo,
                'valorpagamento' => $valor,
                'troco' => $troco ?: null,
                'integracao' => $forma->integracao,
                'codpessoa' => $forma->codpessoa,
                'bandeira' => $forma->bandeira,
                'autorizacao' => $forma->autorizacao,
            ]);
            if ($pag->tipo == 99) {
                $pag->descricao = $forma->FormaPagamento->formapagamento;
            }
            $pag->save();
            $pagamentos[] = $pag;
        }

        if ($valeAgrupado > 0) {
            // entra na FRENTE da lista de propósito: quem absorve a diferença
            // abaixo é o último, e a sobra de centavo tem que cair num
            // pagamento em dinheiro de verdade, não no valor declarado de
            // vale. Só absorve se for o único pagamento da nota.
            array_unshift(
                $pagamentos,
                static::detPagValeAgrupado($nota, $valeAgrupado, $valeAgrupadoAvista)
            );
        }

        // O ULTIMO PAGAMENTO ABSORVE A DIFERENCA.
        //
        // Isso nao e' zelo: a rede do NFePHPMakeService so' corrige para
        // MENOS (quando o vNF e' maior que os pagamentos, ela completa com
        // um tPag 99). Faltar centavo passa; SOBRAR centavo e' rejeicao.
        if (!empty($pagamentos)) {
            $soma = 0;
            foreach ($pagamentos as $pag) {
                $soma += (float) $pag->valorpagamento - (float) $pag->troco;
            }
            $diferenca = round((float) $nota->valortotal - $soma, 2);
            if ($diferenca != 0) {
                $ultimo = end($pagamentos);
                $ultimo->valorpagamento = round((float) $ultimo->valorpagamento + $diferenca, 2);
                $ultimo->save();
            }
        }

        // Duplicatas (so' saem na NFe 55): os titulos a prazo cobrem
        // mercadoria + vale, entao vao para a nota na proporcao do que a
        // nota declara. A ultima duplicata absorve a sobra do arredondamento.
        $proporcao = $negocio->valortotal > 0 ? ($nota->valortotal / $negocio->valortotal) : 0;
        $duplicatas = [];
        foreach ($formas as $forma) {
            foreach ($forma->Titulos as $titulo) {
                $duplicatas[] = [
                    'fatura' => $titulo->numero,
                    'valor' => round(abs($titulo->credito + $titulo->debito) * $proporcao, 2),
                    'vencimento' => $titulo->vencimento,
                    'cheio' => abs($titulo->credito + $titulo->debito),
                ];
            }
        }
        if (!empty($duplicatas)) {
            $somaCheio = array_sum(array_column($duplicatas, 'cheio'));
            $alvo = round($somaCheio * $proporcao, 2);
            $somaRateada = round(array_sum(array_column($duplicatas, 'valor')), 2);
            $duplicatas[count($duplicatas) - 1]['valor'] = round(
                $duplicatas[count($duplicatas) - 1]['valor'] + ($alvo - $somaRateada),
                2
            );
            foreach ($duplicatas as $d) {
                if ($d['valor'] <= 0) {
                    continue;
                }
                (new NotaFiscalDuplicatas([
                    'codnotafiscal' => $nota->codnotafiscal,
                    'fatura' => $d['fatura'],
                    'valor' => $d['valor'],
                    'vencimento' => $d['vencimento'],
                ]))->save();
            }
        }
    }
}
