<?php

namespace Mg\NotaFiscal;

use Exception;
use Illuminate\Support\Facades\DB;
use Mg\Tributacao\TributacaoService;

class NotaFiscalItemService
{
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
        if ($nota->status != NotaFiscalStatusService::STATUS_DIGITACAO) {
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
     * Unifica itens da nota fiscal que possuem o mesmo produto
     * Agrupa itens com mesmo codproduto, somando quantidades e valores
     *
     * @param NotaFiscal $nota Nota fiscal a ter os itens unificados
     * @return void
     */
    public static function unificarItens(NotaFiscal $nota): void
    {
        // Valida se a nota está em digitação
        if ($nota->status !== NotaFiscalStatusService::STATUS_DIGITACAO) {
            throw new Exception("Só é possível unificar itens de notas em digitação. Status atual: {$nota->status}");
        }

        // Busca os codproduto que aparecem mais de uma vez na nota
        // Considera a embalagem para calcular quantidade e valor total
        $produtosDuplicados = DB::select("
            SELECT
                pb.codproduto,
                SUM(nfpb.quantidade * COALESCE(pe.quantidade, 1)) as quantidade,
                min(COALESCE(pe.quantidade, 1)) as embalagem,
                SUM(nfpb.valortotal) as valortotal,
                COUNT(*) as qtditens
            FROM tblnotafiscalprodutobarra nfpb
            INNER JOIN tblprodutobarra pb ON (nfpb.codprodutobarra = pb.codprodutobarra)
            LEFT JOIN tblprodutoembalagem pe ON (pe.codprodutoembalagem = pb.codprodutoembalagem)
            WHERE nfpb.codnotafiscal = ?
            GROUP BY pb.codproduto
            HAVING COUNT(*) > 1
        ", [$nota->codnotafiscal]);

        if (empty($produtosDuplicados)) {
            return; // Nada a unificar
        }

        // Para cada produto duplicado, iguala os precos pela media
        foreach ($produtosDuplicados as $produto) {
            static::equalizarPrecos($nota, $produto);
        }

        // Segunda etapa: agrupa itens com mesma variação
        static::agruparVariacoes($nota);

        // Recalcula totais da nota
        static::recalcularTotais($nota);
    }

    /**
     * Agrupa itens da nota fiscal que possuem a mesma variação
     * Soma quantidades e valores no primeiro item e exclui os demais
     *
     * @param NotaFiscal $nota
     * @return void
     */
    private static function agruparVariacoes(NotaFiscal $nota): void
    {
        // Busca as variações que aparecem mais de uma vez na nota
        $variacoesDuplicadas = DB::select("
            SELECT
                pb.codproduto,
                pb.codprodutovariacao,
                SUM(nfpb.quantidade) as quantidade,
                SUM(nfpb.valortotal) as valortotal,
                SUM(COALESCE(nfpb.valordesconto, 0)) as valordesconto,
                SUM(COALESCE(nfpb.valorfrete, 0)) as valorfrete,
                SUM(COALESCE(nfpb.valorseguro, 0)) as valorseguro,
                SUM(COALESCE(nfpb.valoroutras, 0)) as valoroutras,
                SUM(COALESCE(nfpb.icmsbase, 0)) as icmsbase,
                SUM(COALESCE(nfpb.icmsvalor, 0)) as icmsvalor,
                SUM(COALESCE(nfpb.icmsstbase, 0)) as icmsstbase,
                SUM(COALESCE(nfpb.icmsstvalor, 0)) as icmsstvalor,
                SUM(COALESCE(nfpb.ipibase, 0)) as ipibase,
                SUM(COALESCE(nfpb.ipivalor, 0)) as ipivalor,
                SUM(COALESCE(nfpb.pisbase, 0)) as pisbase,
                SUM(COALESCE(nfpb.pisvalor, 0)) as pisvalor,
                SUM(COALESCE(nfpb.cofinsbase, 0)) as cofinsbase,
                SUM(COALESCE(nfpb.cofinsvalor, 0)) as cofinsvalor,
                SUM(COALESCE(nfpb.irpjbase, 0)) as irpjbase,
                SUM(COALESCE(nfpb.irpjvalor, 0)) as irpjvalor,
                SUM(COALESCE(nfpb.csllbase, 0)) as csllbase,
                SUM(COALESCE(nfpb.csllvalor, 0)) as csllvalor,
                COUNT(*) as qtditens
            FROM tblnotafiscalprodutobarra nfpb
            INNER JOIN tblprodutobarra pb ON (nfpb.codprodutobarra = pb.codprodutobarra)
            LEFT JOIN tblprodutoembalagem pe ON (pe.codprodutoembalagem = pb.codprodutoembalagem)
            WHERE nfpb.codnotafiscal = ?
            GROUP BY pb.codproduto, pb.codprodutovariacao
            HAVING COUNT(*) > 1
        ", [$nota->codnotafiscal]);

        if (empty($variacoesDuplicadas)) {
            return;
        }

        // Para cada variação duplicada, agrupa os itens
        foreach ($variacoesDuplicadas as $variacao) {
            static::agruparItensDaVariacao($nota, $variacao);
        }
    }

    /**
     * Agrupa os itens de uma variação específica na nota fiscal
     *
     * @param NotaFiscal $nota
     * @param $variacao
     * @return void
     */
    private static function agruparItensDaVariacao(NotaFiscal $nota, $variacao): void
    {
        // Busca os itens desta variação na nota
        $itens = NotaFiscalProdutoBarra::select('tblnotafiscalprodutobarra.*')
            ->join('tblprodutobarra as pb', 'tblnotafiscalprodutobarra.codprodutobarra', '=', 'pb.codprodutobarra')
            ->where('tblnotafiscalprodutobarra.codnotafiscal', $nota->codnotafiscal)
            ->where('pb.codprodutovariacao', $variacao->codprodutovariacao)
            ->orderBy('tblnotafiscalprodutobarra.codnotafiscalprodutobarra')
            ->get();

        if ($itens->count() <= 1) {
            return;
        }

        // O primeiro item será mantido e receberá os valores somados
        $itemPrincipal = $itens->first();

        // Atualiza o item principal com os valores agregados
        $itemPrincipal->quantidade = $variacao->quantidade;
        $itemPrincipal->valortotal = $variacao->valortotal;
        $itemPrincipal->valordesconto = $variacao->valordesconto ?: null;
        $itemPrincipal->valorfrete = $variacao->valorfrete ?: null;
        $itemPrincipal->valorseguro = $variacao->valorseguro ?: null;
        $itemPrincipal->valoroutras = $variacao->valoroutras ?: null;
        $itemPrincipal->icmsbase = $variacao->icmsbase ?: null;
        $itemPrincipal->icmsvalor = $variacao->icmsvalor ?: null;
        $itemPrincipal->icmsstbase = $variacao->icmsstbase ?: null;
        $itemPrincipal->icmsstvalor = $variacao->icmsstvalor ?: null;
        $itemPrincipal->ipibase = $variacao->ipibase ?: null;
        $itemPrincipal->ipivalor = $variacao->ipivalor ?: null;
        $itemPrincipal->pisbase = $variacao->pisbase ?: null;
        $itemPrincipal->pisvalor = $variacao->pisvalor ?: null;
        $itemPrincipal->cofinsbase = $variacao->cofinsbase ?: null;
        $itemPrincipal->cofinsvalor = $variacao->cofinsvalor ?: null;
        $itemPrincipal->irpjbase = $variacao->irpjbase ?: null;
        $itemPrincipal->irpjvalor = $variacao->irpjvalor ?: null;
        $itemPrincipal->csllbase = $variacao->csllbase ?: null;
        $itemPrincipal->csllvalor = $variacao->csllvalor ?: null;
        $itemPrincipal->save();

        // Soma os tributos de todos os itens agrupados por codtributo
        $codItens = $itens->pluck('codnotafiscalprodutobarra')->toArray();
        $tributosSomados = DB::select("
            SELECT
                codtributo,
                SUM(COALESCE(base, 0)) as base,
                SUM(COALESCE(valor, 0)) as valor,
                SUM(COALESCE(valorcredito, 0)) as valorcredito
            FROM tblnotafiscalitemtributo
            WHERE codnotafiscalprodutobarra IN (" . implode(',', $codItens) . ")
            GROUP BY codtributo
        ");

        // Atualiza os tributos do item principal com os valores somados
        foreach ($tributosSomados as $tributo) {
            $itemTributo = NotaFiscalItemTributo::where('codnotafiscalprodutobarra', $itemPrincipal->codnotafiscalprodutobarra)
                ->where('codtributo', $tributo->codtributo)
                ->first();

            if ($itemTributo) {
                $itemTributo->base = $tributo->base ?: null;
                $itemTributo->valor = $tributo->valor ?: null;
                $itemTributo->valorcredito = $tributo->valorcredito ?: null;
                $itemTributo->save();
            }
        }

        // Exclui os demais itens
        $itensParaExcluir = $itens->slice(1);
        foreach ($itensParaExcluir as $item) {
            // Exclui os tributos do item
            $item->NotaFiscalItemTributoS()->delete();
            // Exclui o item
            $item->delete();
        }
    }

    /**
     * Unifica os itens de um produto específico na nota fiscal
     *
     * @param NotaFiscal $nota
     * @param $produto
     * @return void
     */
    private static function equalizarPrecos(NotaFiscal $nota, $produto): void
    {
        // Busca os itens deste produto na nota
        $itens = NotaFiscalProdutoBarra::select('tblnotafiscalprodutobarra.*')
            ->join('tblprodutobarra as pb', 'tblnotafiscalprodutobarra.codprodutobarra', '=', 'pb.codprodutobarra')
            ->where('tblnotafiscalprodutobarra.codnotafiscal', $nota->codnotafiscal)
            ->where('pb.codproduto', $produto->codproduto)
            ->orderBy('tblnotafiscalprodutobarra.codnotafiscalprodutobarra')
            ->get();
        if ($itens->count() <= 1) {
            return;
        }

        // calcula novo preco
        $preco = round($produto->valortotal / $produto->quantidade, 2);

        // percorre todos os itens daquela produto, igualando preco e embalagem
        foreach ($itens as $item) {

            // descobre em qual embalagem o item está
            $embOriginal = 1;
            if ($item->ProdutoBarra->codprodutoembalagem) {
                $embOriginal = $item->ProdutoBarra->ProdutoEmbalagem->quantidade;
            }

            // se a embalagem do item é diferente da embalagem que vamos usar para o produto
            if ($embOriginal != $produto->embalagem) {

                // busca um codigo de barras na embalagem nova
                $sql = '
                    SELECT pb.codprodutobarra
                    FROM tblprodutobarra pb
                    LEFT JOIN tblprodutoembalagem pe ON (pb.codprodutoembalagem = pe.codprodutoembalagem)
                    WHERE pb.codprodutovariacao = :codprodutovariacao
                    AND COALESCE(pe.quantidade, 1) = :embalagem
                ';
                $ret = DB::select($sql, [
                    'codprodutovariacao' => $item->ProdutoBarra->codprodutovariacao,
                    'embalagem' => $produto->embalagem
                ]);
                if (!$ret) {
                    throw new Exception("Não existe código de barras disponível para o produto '{$item->ProdutoBarra->Produto->produto}' Variação '{$item->ProdutoBarra->ProdutoVariacao->variacao}' Embalagem '{$produto->embalagem}'!", 1);
                }

                // converte a quantidade da embalagem antiga para a nova
                $item->codprodutobarra = $ret[0]->codprodutobarra;
                $item->quantidade = ($item->quantidade * $embOriginal) / $produto->embalagem;
            }

            // define novo preco e novo valor total
            $item->valorunitario = $preco * $produto->embalagem;
            $item->valortotal = $item->valorunitario * $item->quantidade;
            $item->save();

            // Recarrega o item com os relacionamentos necessários para calcularTributacao
            $item = NotaFiscalProdutoBarra::with([
                'NotaFiscal.Filial.Pessoa.Cidade.Estado',
                'NotaFiscal.Pessoa.Cidade.Estado',
                'NotaFiscal.NaturezaOperacao',
                'ProdutoBarra.Produto.Ncm',
                'ProdutoBarra.ProdutoVariacao',
                'ProdutoBarra.ProdutoEmbalagem',
            ])->find($item->codnotafiscalprodutobarra);

            // Recalcular impostos com base no novo valor
            NotaFiscalProdutoBarraService::calcularTributacao($item, false);
            TributacaoService::recalcularTributosItem($item);
            $item->save();
        }
    }
}
