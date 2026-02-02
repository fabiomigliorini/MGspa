<?php

namespace Mg\NotaFiscal;

use Exception;
use Mg\NfeTerceiro\NfeTerceiro;

class NotaFiscalDevolucaoService
{
    /**
     * Gera uma nota de devolução baseada em uma nota fiscal original
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
        $statusValidos = [NotaFiscalStatusService::STATUS_AUTORIZADA, NotaFiscalStatusService::STATUS_LANCADA];
        if (!in_array($notaOriginal->status, $statusValidos)) {
            throw new Exception("Só é possível gerar devolução de notas autorizadas ou lançadas. Status atual: {$notaOriginal->status}");
        }

        // Valida se a nota original tem chave NFe
        if (empty($notaOriginal->nfechave)) {
            throw new Exception("A nota fiscal original não possui chave NFe!");
        }

        if (empty($notaOriginal->NaturezaOperacao->codnaturezaoperacaodevolucao)) {
            throw new Exception("Natureza de operação '{$notaOriginal->NaturezaOperacao->naturezaoperacao}' não tem cadastro de Natureza vinculada para Devolução!");
        }

        // Indexa os itens da requisição por codnotafiscalprodutobarra
        $itensRequest = collect($itens)->keyBy('codnotafiscalprodutobarra');

        // Cria a nova nota com os dados da original
        $notaNova = new NotaFiscal();
        $notaNova->fill($notaOriginal->getAttributes());

        // Define o codpessoa da devolução
        $notaNova->codpessoa = $codpessoa;

        // Atualiza os campos específicos para devolução
        $notaNova->modelo = NotaFiscalService::MODELO_NFE;
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
        $notaNova->status = NotaFiscalStatusService::STATUS_DIGITACAO;
        $notaNova->nfeimpressa = false;
        $notaNova->codnaturezaoperacao = $notaOriginal->NaturezaOperacao->codnaturezaoperacaodevolucao;
        $notaNova->codoperacao = $notaNova->NaturezaOperacao->codoperacao;
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
        NotaFiscalItemService::recalcularTotais($notaNova);

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
}
