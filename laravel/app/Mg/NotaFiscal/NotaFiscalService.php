<?php

namespace Mg\NotaFiscal;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Tributacao\TributacaoService;

class NotaFiscalService
{
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

    public static function excluir(NotaFiscal $nf)
    {
        if ($nf->emitida) {
            if (!empty($nf->numero)) {
                throw new Exception("Nota Fiscal já possui atribuição de um Número. Ao invés de excluir tente Inutilizar!", 1);
            }
        }
        return $nf->delete();
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
        $notaNova->status = NotaFiscalStatusService::STATUS_DIGITACAO;

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
            NotaFiscalItemService::recalcularTributacao($notaNova);
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
     * Unifica múltiplas notas fiscais em uma única nota
     *
     * @param NotaFiscal $notaDestino Nota fiscal que receberá os itens
     * @param array $codigosNotas Array com os códigos das notas a serem unificadas
     * @return NotaFiscal
     */
    public static function unificarNotas(NotaFiscal $notaDestino, array $codigosNotas): NotaFiscal
    {
        // Valida se a nota destino está em digitação
        if ($notaDestino->status !== NotaFiscalStatusService::STATUS_DIGITACAO) {
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
            if ($notaOrigem->status !== NotaFiscalStatusService::STATUS_DIGITACAO) {
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
        NotaFiscalItemService::recalcularTotais($notaDestino);

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
