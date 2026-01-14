<?php

namespace Mg\NotaFiscal;

use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Mg\Negocio\Negocio;
use Mg\Pessoa\PessoaService;
use Mg\Negocio\NegocioProdutoBarraService;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacaoService;
use Mg\Negocio\NegocioService;
use Mg\Tributacao\TributacaoService;
use Mg\Pessoa\Pessoa;
use Mg\NfeTerceiro\NfeTerceiro;

class NotaFiscalService
{
    // Status da Nota Fiscal
    const STATUS_LANCADA             = 'LAN'; // Lançada (emitida = false)
    const STATUS_DIGITACAO           = 'DIG'; // Em Digitação (emitida = true e numero vazio)
    const STATUS_ERRO                = 'ERR'; // Não Autorizada (emitida = true, tem número, sem autorização)
    const STATUS_AUTORIZADA          = 'AUT'; // Autorizada (nfeautorizacao preenchido e não cancelada/inutilizada)
    const STATUS_CANCELADA           = 'CAN'; // Cancelada (nfecancelamento preenchido)
    const STATUS_INUTILIZADA         = 'INU'; // Inutilizada (nfeinutilizacao preenchido)
    const STATUS_DENEGADA            = 'DEN'; // Denegada

    const STATUS_LABELS = [
        self::STATUS_LANCADA     => 'Lançada',
        self::STATUS_DIGITACAO   => 'Em Digitação',
        self::STATUS_ERRO        => 'Não Autorizada',
        self::STATUS_AUTORIZADA  => 'Autorizada',
        self::STATUS_CANCELADA   => 'Cancelada',
        self::STATUS_INUTILIZADA => 'Inutilizada',
        self::STATUS_DENEGADA    => 'Denegada',
    ];

    const MODELO_NFE                 = 55;
    const MODELO_NFCE                = 65;

    const FRETE_EMITENTE             = 0;
    const FRETE_DESTINATARIO         = 1;
    const FRETE_TERCEIROS            = 2;
    const FRETE_EMITENTE_PROPRIO     = 3;
    const FRETE_DESTINATARIO_PROPRIO = 4;
    const FRETE_SEM                  = 9;

    const FRETE_LABELS = [
        self::FRETE_EMITENTE             => 'Por conta do emitente',
        self::FRETE_DESTINATARIO         => 'Por conta do destinatário',
        self::FRETE_TERCEIROS            => 'Por conta de terceiros',
        self::FRETE_EMITENTE_PROPRIO     => 'Transporte próprio por conta do remetente',
        self::FRETE_DESTINATARIO_PROPRIO => 'Transporte próprio por conta do destinatário',
        self::FRETE_SEM                  => 'Sem frete',
    ];

    const TPEMIS_NORMAL              = 1; // Emissão normal (não em contingência);
    const TPEMIS_FS_IA               = 2; // Contingência FS-IA, com impressão do DANFE em formulário de segurança;
    const TPEMIS_SCAN                = 3; // Contingência SCAN (Sistema de Contingência do Ambiente Nacional) Desativação prevista para 30/06/2014;
    const TPEMIS_DPEC                = 4; // Contingência DPEC (Declaração Prévia da Emissão em Contingência);
    const TPEMIS_FS_DA               = 5; // Contingência FS-DA, com impressão do DANFE em formulário de segurança;
    const TPEMIS_SVC_AN              = 6; // Contingência SVC-AN (SEFAZ Virtual de Contingência do AN);
    const TPEMIS_SVC_RS              = 7; // Contingência SVC-RS (SEFAZ Virtual de Contingência do RS);
    const TPEMIS_OFFLINE             = 9; // Contingência off-line da NFC-e (as demais opções de contingência são válidas também para a NFC-e);

    /**
     * Calcula o status atual da nota baseado nos campos
     */
    public static function calcularStatus(NotaFiscal $nota): string
    {
        if (!$nota->emitida) {
            return static::STATUS_LANCADA;
        }

        if (!empty($nota->nfeinutilizacao)) {
            return static::STATUS_INUTILIZADA;
        }

        if (!empty($nota->nfecancelamento)) {
            return static::STATUS_CANCELADA;
        }

        if (!empty($nota->nfeautorizacao)) {
            return static::STATUS_AUTORIZADA;
        }

        if (empty($nota->numero)) {
            return static::STATUS_DIGITACAO;
        }

        return static::STATUS_ERRO;
    }

    /**
     * Atualiza o campo status da nota
     */
    public static function atualizarStatus(NotaFiscal $nota): void
    {
        $novoStatus = static::calcularStatus($nota);

        if ($nota->status !== $novoStatus) {
            $nota->status = $novoStatus;
            $nota->saveQuietly(); // Salva sem disparar eventos
        }
    }

    /**
     * Retorna o status atual da nota (DEPRECATED: use $nota->status)
     * @deprecated Use o campo $nota->status ao invés deste método
     */
    public static function getStatusNota(NotaFiscal $nota): string
    {
        // Retorna o status calculado para compatibilidade
        return static::calcularStatus($nota);
    }

    public static function isInutilizada(NotaFiscal $nota): bool
    {
        return !empty($nota->nfeinutilizacao);
    }

    public static function isCancelada(NotaFiscal $nota): bool
    {
        return !empty($nota->nfecancelamento);
    }

    public static function isAutorizada(NotaFiscal $nota): bool
    {
        return !empty($nota->nfeautorizacao)
            && !static::isCancelada($nota)
            && !static::isInutilizada($nota);
    }

    public static function isCanceladaInutilizada(NotaFiscal $nota): bool
    {
        return static::isCancelada($nota) || static::isInutilizada($nota);
    }

    /**
     * Verifica se a nota pode ser editada
     */
    public static function isEditable(NotaFiscal $nota): bool
    {
        $statusBloqueados = [
            static::STATUS_AUTORIZADA,
            static::STATUS_CANCELADA,
            static::STATUS_INUTILIZADA,
            static::STATUS_ERRO,
        ];

        return !in_array($nota->status, $statusBloqueados);
    }

    public static function isAtiva(NotaFiscal $nota): bool
    {
        if (static::isAutorizada($nota)) {
            return true;
        }
        if (!$nota->emitida && !empty($nota->numero)) {
            return true;
        }
        return false;
    }

    public static function isDigitacao(NotaFiscal $nota): bool
    {
        return (empty($nota->numero) && ($nota->emitida));
    }

    // Gera nota fiscal a partir do negocio
    public static function gerarNotaFiscalDoNegocio(
        Negocio $negocio,
        $modelo = self::MODELO_NFCE,
        $incluirPagamentos = true,
        ?NotaFiscal $nota = null
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

    public static function excluir(NotaFiscal $nf)
    {
        if ($nf->emitida) {
            if (!empty($nf->numero)) {
                throw new Exception("Nota Fiscal já possui atribuição de um Número. Ao invés de excluir tente Inutilizar!", 1);
            }
        }
        return $nf->delete();
    }

    public static function notasDoNegocio($codnegocio)
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

    public static function recalcularTributacao(NotaFiscal $nota): void
    {
        foreach ($nota->NotaFiscalProdutoBarraS as $nfpb) {
            NotaFiscalProdutoBarraService::calcularTributacao($nfpb, false);
            TributacaoService::recalcularTributosItem($nfpb);  // Reforma Tributaria
            $nfpb->save();
        }
    }

    /**
     * Incorpora os valores de frete, seguro, desconto e outras no valor unitário dos itens
     * zerando esses campos após a incorporação
     */
    public static function incorporarValores(NotaFiscal $nota): void
    {
        if ($nota->status != static::STATUS_DIGITACAO) {
            throw new Exception("Nota Fiscal não está em Digitação!", 1);
        }

        // Carrega os itens se não estiverem carregados
        if (!$nota->relationLoaded('NotaFiscalProdutoBarraS')) {
            $nota->load('NotaFiscalProdutoBarraS');
        }

        $itens = $nota->NotaFiscalProdutoBarraS;
        if ($itens->isEmpty()) {
            return;
        }

        $totalAntigo = $nota->valortotal;
        $total = 0;

        // Primeira passada: incorpora valores no unitário
        foreach ($itens as $item) {
            $valorFinal = $item->valortotal
                + ($item->valorfrete ?? 0)
                + ($item->valorseguro ?? 0)
                - ($item->valordesconto ?? 0)
                + ($item->valoroutras ?? 0);

            $item->valorunitario = round($valorFinal / $item->quantidade, 2);
            $item->valortotal = round($item->valorunitario * $item->quantidade, 2);
            $item->valorfrete = null;
            $item->valorseguro = null;
            $item->valordesconto = null;
            $item->valoroutras = null;

            NotaFiscalProdutoBarraService::calcularTributacao($item, false);
            TributacaoService::recalcularTributosItem($item);
            $item->save();

            $total += $item->valortotal;
        }

        // Segunda passada: ajusta diferença de arredondamento
        if ($total != $totalAntigo) {
            $dif = round($totalAntigo - $total, 2);
            foreach ($itens as $item) {
                // Só ajusta se a diferença for divisível pela quantidade
                if (($dif * 100) % $item->quantidade == 0) {
                    $item->valorunitario = round(($item->valortotal + $dif) / $item->quantidade, 2);
                    $item->valortotal = round($item->valorunitario * $item->quantidade, 2);

                    NotaFiscalProdutoBarraService::calcularTributacao($item, false);
                    TributacaoService::recalcularTributosItem($item);
                    $item->save();

                    $total += $dif;
                    break;
                }
            }
        }

        // Atualiza totais da nota
        $nota->valorprodutos = $total;
        $nota->valortotal = $total;
        $nota->valorfrete = null;
        $nota->valorseguro = null;
        $nota->valordesconto = null;
        $nota->valoroutras = null;
        $nota->save();
    }

    /**
     * Recalcula os totais da nota fiscal a partir dos itens
     */
    public static function recalcularTotais(NotaFiscal $nota): void
    {
        // Carrega os itens se não estiverem carregados
        if (!$nota->relationLoaded('NotaFiscalProdutoBarraS')) {
            $nota->load('NotaFiscalProdutoBarraS');
        }

        $itens = $nota->NotaFiscalProdutoBarraS;

        // Somatórios dos valores
        $nota->valorprodutos = $itens->sum('valortotal');
        $nota->valordesconto = $itens->sum('valordesconto');
        $nota->valorfrete = $itens->sum('valorfrete');
        $nota->valorseguro = $itens->sum('valorseguro');
        $nota->valoroutras = $itens->sum('valoroutras');

        // Calcula valor total
        $nota->valortotal = $nota->valorprodutos
            - $nota->valordesconto
            + $nota->valorfrete
            + $nota->valorseguro
            + $nota->valoroutras;

        // Somatórios dos impostos
        $nota->icmsbase = $itens->sum('icmsbase');
        $nota->icmsvalor = $itens->sum('icmsvalor');
        $nota->icmsstbase = $itens->sum('icmsstbase');
        $nota->icmsstvalor = $itens->sum('icmsstvalor');
        $nota->ipibase = $itens->sum('ipibase');
        $nota->ipivalor = $itens->sum('ipivalor');

        $nota->save();
    }

    /**
     * Rateia valores (desconto, frete, seguro, outras) entre os itens da nota fiscal
     * proporcional ao valor total de cada item
     */
    public static function ratearValoresItens(NotaFiscal $nota, array $valoresAntigos): void
    {
        // Campos que devem ser rateados
        $camposRateio = ['valordesconto', 'valorfrete', 'valorseguro', 'valoroutras'];

        // Verifica se algum valor foi alterado
        $temAlteracao = false;
        foreach ($camposRateio as $campo) {
            $valorNovo = $nota->{$campo} ?? 0;
            $valorAntigo = $valoresAntigos[$campo] ?? 0;
            if (abs($valorNovo - $valorAntigo) > 0.001) {
                $temAlteracao = true;
                break;
            }
        }

        if (!$temAlteracao) {
            return;
        }

        // Carrega os itens se não estiverem carregados
        if (!$nota->relationLoaded('NotaFiscalProdutoBarraS')) {
            $nota->load('NotaFiscalProdutoBarraS');
        }

        $itens = $nota->NotaFiscalProdutoBarraS;
        if ($itens->isEmpty()) {
            return;
        }

        // Calcula o valor total dos produtos (soma de valortotal de cada item)
        $valorTotalProdutos = $itens->sum('valortotal');
        if ($valorTotalProdutos <= 0) {
            return;
        }

        // Rateia cada campo proporcional ao valor do item
        foreach ($camposRateio as $campo) {
            $valorTotal = $nota->{$campo} ?? 0;
            $valorDistribuido = 0;

            foreach ($itens as $index => $item) {
                // Se for o último item, joga a diferença de arredondamento
                if ($index === $itens->count() - 1) {
                    $valorRateado = round($valorTotal - $valorDistribuido, 2);
                } else {
                    // Calcula proporcional
                    $proporcao = $item->valortotal / $valorTotalProdutos;
                    $valorRateado = round($valorTotal * $proporcao, 2);
                    $valorDistribuido += $valorRateado;
                }

                $item->{$campo} = $valorRateado;
            }
        }

        // Recalcula tributação e salva cada item
        foreach ($itens as $item) {
            NotaFiscalProdutoBarraService::calcularTributacao($item, false);
            TributacaoService::recalcularTributosItem($item);
            $item->save();
        }

        // Recarrega os itens e recalcula os totais da nota
        $nota->load('NotaFiscalProdutoBarraS');
        static::recalcularTotais($nota);
    }

    /**
     * Duplica uma nota fiscal, criando uma nova cópia em digitação
     */
    public static function duplicar(NotaFiscal $notaOriginal): NotaFiscal
    {

        // Cria a nova nota com os dados da original
        $notaNova = new NotaFiscal();
        $notaNova->fill($notaOriginal->getAttributes());

        // Atualiza os campos específicos conforme solicitado
        $notaNova->serie = 1;
        $notaNova->numero = 0;
        $notaNova->emissao = date('Y-m-d H:i:s');
        $notaNova->saida = $notaNova->emissao;
        $notaNova->nfechave = null;
        $notaNova->nfereciboenvio = null;
        $notaNova->nfedataenvio = null;
        $notaNova->nfeautorizacao = null;
        $notaNova->nfedataautorizacao = null;
        $notaNova->nfecancelamento = null;
        $notaNova->nfedatacancelamento = null;
        $notaNova->nfeinutilizacao = null;
        $notaNova->nfedatainutilizacao = null;
        $notaNova->justificativa = null;
        $notaNova->status = static::STATUS_DIGITACAO;

        // Limpa outros campos relacionados à SEFAZ
        $notaNova->nfereciboenvio = null;
        $notaNova->nfedataenvio = null;
        $notaNova->nfedataautorizacao = null;
        $notaNova->nfedatacancelamento = null;
        $notaNova->nfedatainutilizacao = null;
        $notaNova->nfeimpressa = false;

        $notaNova->save();


        // Duplica os itens da nota (NotaFiscalProdutoBarra)
        foreach ($notaOriginal->NotaFiscalProdutoBarraS as $itemOriginal) {
            $itemNovo = new NotaFiscalProdutoBarra();
            $itemNovo->fill($itemOriginal->getAttributes());
            $itemNovo->codnotafiscal = $notaNova->codnotafiscal;
            $itemNovo->save();

            // Duplica os tributos do item (NotaFiscalItemTributo)
            foreach ($itemOriginal->NotaFiscalItemTributoS as $tributoOriginal) {
                $tributoNovo = new NotaFiscalItemTributo();
                $tributoNovo->fill($tributoOriginal->getAttributes());
                $tributoNovo->codnotafiscalprodutobarra = $itemNovo->codnotafiscalprodutobarra;
                $tributoNovo->save();
            }
        }

        // Duplica as notas referenciadas
        foreach ($notaOriginal->NotaFiscalReferenciadaS as $referenciadaOriginal) {
            $referenciadaNova = new NotaFiscalReferenciada();
            $referenciadaNova->fill($referenciadaOriginal->getAttributes());
            $referenciadaNova->codnotafiscal = $notaNova->codnotafiscal;
            $referenciadaNova->save();
        }

        // Duplica os pagamentos
        foreach ($notaOriginal->NotaFiscalPagamentoS as $pagamentoOriginal) {
            $pagamentoNovo = new NotaFiscalPagamento();
            $pagamentoNovo->fill($pagamentoOriginal->getAttributes());
            $pagamentoNovo->codnotafiscal = $notaNova->codnotafiscal;
            $pagamentoNovo->save();
        }

        // Duplica as duplicatas
        foreach ($notaOriginal->NotaFiscalDuplicatasS as $duplicataOriginal) {
            $duplicataNova = new NotaFiscalDuplicatas();
            $duplicataNova->fill($duplicataOriginal->getAttributes());
            $duplicataNova->codnotafiscal = $notaNova->codnotafiscal;
            $duplicataNova->save();
        }

        try {
            NotaFiscalService::recalcularTributacao($notaNova);
        } catch (\Throwable $th) {
            Log::error("Erro ao recalcular tributacao da NF #{$notaNova->codnotafiscal}", $th);
        }

        // Recarrega a nota com todos os relacionamentos
        return NotaFiscal::with([
            'Filial',
            'EstoqueLocal',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ])->findOrFail($notaNova->codnotafiscal);
    }

    /**
     * Gera uma nota fiscal de devolução parcial a partir de uma nota existente
     *
     * @param NotaFiscal $notaOriginal Nota fiscal original
     * @param array $itens Array com os itens a devolver no formato:
     *                     [['codnotafiscalprodutobarra' => 999, 'quantidade' => 3], ...]
     * @param int $codpessoa Código da pessoa para a nota de devolução
     * @return NotaFiscal
     */
    public static function gerarDevolucao(NotaFiscal $notaOriginal, array $itens, int $codpessoa): NotaFiscal
    {
        // Valida se a nota original está autorizada ou lançada
        $statusValidos = [static::STATUS_AUTORIZADA, static::STATUS_LANCADA];
        if (!in_array($notaOriginal->status, $statusValidos)) {
            throw new Exception("Só é possível gerar devolução de notas autorizadas ou lançadas. Status atual: {$notaOriginal->status}");
        }

        // Valida se a nota original tem chave NFe
        if (empty($notaOriginal->nfechave)) {
            throw new Exception("A nota fiscal original não possui chave NFe!");
        }

        // Indexa os itens da requisição por codnotafiscalprodutobarra
        $itensRequest = collect($itens)->keyBy('codnotafiscalprodutobarra');

        // Cria a nova nota com os dados da original
        $notaNova = new NotaFiscal();
        $notaNova->fill($notaOriginal->getAttributes());

        // Define o codpessoa da devolução
        $notaNova->codpessoa = $codpessoa;

        // Atualiza os campos específicos para devolução
        $notaNova->serie = 1;
        $notaNova->numero = 0;
        $notaNova->emissao = date('Y-m-d H:i:s');
        $notaNova->saida = $notaNova->emissao;
        $notaNova->emitida = true;
        $notaNova->nfechave = null;
        $notaNova->nfereciboenvio = null;
        $notaNova->nfedataenvio = null;
        $notaNova->nfeautorizacao = null;
        $notaNova->nfedataautorizacao = null;
        $notaNova->nfecancelamento = null;
        $notaNova->nfedatacancelamento = null;
        $notaNova->nfeinutilizacao = null;
        $notaNova->nfedatainutilizacao = null;
        $notaNova->justificativa = null;
        $notaNova->status = static::STATUS_DIGITACAO;
        $notaNova->nfeimpressa = false;
        $notaNova->codnaturezaoperacao = $notaOriginal->NaturezaOperacao->codnaturezaoperacaodevolucao;
        $notaNova->observacoes = "Devolução da Nota Fiscal {$notaOriginal->numero} modelo {$notaOriginal->modelo}";

        // Zera os totais - serão recalculados
        $notaNova->valorprodutos = 0;
        $notaNova->valortotal = 0;
        $notaNova->valordesconto = 0;
        $notaNova->valorfrete = 0;
        $notaNova->valorseguro = 0;
        $notaNova->valoroutras = 0;
        $notaNova->icmsbase = 0;
        $notaNova->icmsvalor = 0;
        $notaNova->icmsstbase = 0;
        $notaNova->icmsstvalor = 0;
        $notaNova->ipibase = 0;
        $notaNova->ipivalor = 0;

        $notaNova->save();

        // Adiciona a referência à nota original
        $nfReferenciada = new NotaFiscalReferenciada([
            'codnotafiscal' => $notaNova->codnotafiscal,
            'nfechave' => $notaOriginal->nfechave,
        ]);
        $nfReferenciada->save();

        // Busca NFe Terceiro pela chave da nota original para pegar xprod
        $nfeTerceiro = NfeTerceiro::with('NfeTerceiroItemS')
            ->where('nfechave', $notaOriginal->nfechave)
            ->first();

        // Indexa itens da NFe Terceiro por nitem para busca rápida
        $itensTerceiro = $nfeTerceiro
            ? $nfeTerceiro->NfeTerceiroItemS->keyBy('nitem')
            : collect();

        // Duplica apenas os itens solicitados com as quantidades especificadas
        foreach ($notaOriginal->NotaFiscalProdutoBarraS as $itemOriginal) {
            // Verifica se este item está na lista de itens a devolver
            if (!$itensRequest->has($itemOriginal->codnotafiscalprodutobarra)) {
                continue;
            }

            $itemRequest = $itensRequest->get($itemOriginal->codnotafiscalprodutobarra);
            $quantidadeDevolucao = $itemRequest['quantidade'];

            // Valida quantidade
            if ($quantidadeDevolucao > $itemOriginal->quantidade) {
                throw new Exception(
                    "Quantidade de devolução ({$quantidadeDevolucao}) maior que a quantidade original ({$itemOriginal->quantidade}) para o item #{$itemOriginal->codnotafiscalprodutobarra}"
                );
            }

            // Calcula a proporção para recalcular os valores
            $proporcao = $quantidadeDevolucao / $itemOriginal->quantidade;

            // Cria o novo item
            $itemNovo = new NotaFiscalProdutoBarra();
            $itemNovo->fill($itemOriginal->getAttributes());
            $itemNovo->codnotafiscal = $notaNova->codnotafiscal;
            $itemNovo->quantidade = $quantidadeDevolucao;

            // Recalcula tributação para buscar o CFOP
            $itemNovo->codcfop = null;
            NotaFiscalProdutoBarraService::calcularTributacao($itemNovo);

            // Busca xprod da NFe Terceiro pelo nitem (ordem do item)
            if ($itensTerceiro->has($itemOriginal->ordem)) {
                $itemNovo->descricaoalternativa = $itensTerceiro->get($itemOriginal->ordem)->xprod;
            }

            // Recalcula valores proporcionais
            $itemNovo->valortotal = round($itemOriginal->valortotal * $proporcao, 2);
            $itemNovo->valordesconto = round(($itemOriginal->valordesconto ?? 0) * $proporcao, 2);
            $itemNovo->valorfrete = round(($itemOriginal->valorfrete ?? 0) * $proporcao, 2);
            $itemNovo->valorseguro = round(($itemOriginal->valorseguro ?? 0) * $proporcao, 2);
            $itemNovo->valoroutras = round(($itemOriginal->valoroutras ?? 0) * $proporcao, 2);

            // Recalcula valores de impostos proporcionais
            $itemNovo->icmsbase = round(($itemOriginal->icmsbase ?? 0) * $proporcao, 2);
            $itemNovo->icmsvalor = round(($itemOriginal->icmsvalor ?? 0) * $proporcao, 2);
            $itemNovo->icmsstbase = round(($itemOriginal->icmsstbase ?? 0) * $proporcao, 2);
            $itemNovo->icmsstvalor = round(($itemOriginal->icmsstvalor ?? 0) * $proporcao, 2);
            $itemNovo->ipibase = round(($itemOriginal->ipibase ?? 0) * $proporcao, 2);
            $itemNovo->ipivalor = round(($itemOriginal->ipivalor ?? 0) * $proporcao, 2);
            $itemNovo->pisbase = round(($itemOriginal->pisbase ?? 0) * $proporcao, 2);
            $itemNovo->pisvalor = round(($itemOriginal->pisvalor ?? 0) * $proporcao, 2);
            $itemNovo->cofinsbase = round(($itemOriginal->cofinsbase ?? 0) * $proporcao, 2);
            $itemNovo->cofinsvalor = round(($itemOriginal->cofinsvalor ?? 0) * $proporcao, 2);

            // Referencia o item original
            $itemNovo->codnotafiscalprodutobarraorigem = $itemOriginal->codnotafiscalprodutobarra;

            $itemNovo->save();

            // Duplica os tributos do item com valores proporcionais
            foreach ($itemOriginal->NotaFiscalItemTributoS as $tributoOriginal) {
                $tributoNovo = new NotaFiscalItemTributo();
                $tributoNovo->fill($tributoOriginal->getAttributes());
                $tributoNovo->codnotafiscalprodutobarra = $itemNovo->codnotafiscalprodutobarra;

                // Recalcula valores proporcionais
                $tributoNovo->base = round(($tributoOriginal->base ?? 0) * $proporcao, 2);
                $tributoNovo->valor = round(($tributoOriginal->valor ?? 0) * $proporcao, 2);
                $tributoNovo->valorcredito = round(($tributoOriginal->valorcredito ?? 0) * $proporcao, 2);

                $tributoNovo->save();
            }
        }

        // Recalcula os totais da nota
        static::recalcularTotais($notaNova);

        // Recarrega a nota com todos os relacionamentos
        return NotaFiscal::with([
            'Filial',
            'EstoqueLocal',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ])->findOrFail($notaNova->codnotafiscal);
    }

    /**
     * Unifica múltiplas notas fiscais em uma única nota
     *
     * @param NotaFiscal $notaDestino Nota fiscal que receberá os itens
     * @param array $codigosNotas Array com os códigos das notas a serem unificadas
     * @return NotaFiscal
     */
    public static function unificarNotas(NotaFiscal $notaDestino, array $codigosNotas): NotaFiscal
    {
        // Valida se a nota destino está em digitação
        if ($notaDestino->status !== static::STATUS_DIGITACAO) {
            throw new Exception("A nota destino não está em digitação. Status atual: {$notaDestino->status}");
        }

        // Carrega as notas a serem unificadas com todos os relacionamentos
        $notasOrigem = NotaFiscal::with([
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
        ])->whereIn('codnotafiscal', $codigosNotas)->get();

        foreach ($notasOrigem as $notaOrigem) {
            // Valida se a nota origem está em digitação
            if ($notaOrigem->status !== static::STATUS_DIGITACAO) {
                throw new Exception("A nota #{$notaOrigem->codnotafiscal} não está em digitação. Status: {$notaOrigem->status}");
            }

            // Valida se tem mesma natureza de operação
            if ($notaOrigem->codnaturezaoperacao !== $notaDestino->codnaturezaoperacao) {
                throw new Exception("A nota #{$notaOrigem->codnotafiscal} possui natureza de operação diferente");
            }

            // Valida se tem mesma pessoa
            if ($notaOrigem->codpessoa !== $notaDestino->codpessoa) {
                throw new Exception("A nota #{$notaOrigem->codnotafiscal} possui pessoa diferente");
            }

            // Move os itens (NotaFiscalProdutoBarra)
            foreach ($notaOrigem->NotaFiscalProdutoBarraS as $item) {
                $item->codnotafiscal = $notaDestino->codnotafiscal;
                $item->save();
            }

            // Move as notas referenciadas
            foreach ($notaOrigem->NotaFiscalReferenciadaS as $referenciada) {
                // Verifica se já não existe essa referência na nota destino
                $existe = NotaFiscalReferenciada::where('codnotafiscal', $notaDestino->codnotafiscal)
                    ->where('nfechave', $referenciada->nfechave)
                    ->exists();

                if (!$existe) {
                    $referenciada->codnotafiscal = $notaDestino->codnotafiscal;
                    $referenciada->save();
                } else {
                    $referenciada->delete();
                }
            }

            // Move os pagamentos
            foreach ($notaOrigem->NotaFiscalPagamentoS as $pagamento) {
                $pagamento->codnotafiscal = $notaDestino->codnotafiscal;
                $pagamento->save();
            }

            // Move as duplicatas
            foreach ($notaOrigem->NotaFiscalDuplicatasS as $duplicata) {
                $duplicata->codnotafiscal = $notaDestino->codnotafiscal;
                $duplicata->save();
            }

            // Exclui a nota origem (agora vazia)
            $notaOrigem->delete();
        }

        // Recalcula os totais da nota destino
        static::recalcularTotais($notaDestino);

        // Recarrega a nota com todos os relacionamentos
        return NotaFiscal::with([
            'Filial',
            'EstoqueLocal',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ])->findOrFail($notaDestino->codnotafiscal);
    }
}
