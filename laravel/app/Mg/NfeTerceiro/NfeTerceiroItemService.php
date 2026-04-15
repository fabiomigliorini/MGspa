<?php

namespace Mg\NfeTerceiro;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Mg\NFePHP\NFePHPManifestacaoService;
use Mg\Produto\ProdutoService;

class NfeTerceiroItemService
{
    public static function buscar(int $codnfeterceiro, string $barras): array
    {
        $barras = trim($barras);

        return DB::select('
            select nti.codnfeterceiroitem, nti.cprod, nti.xprod, nti.cean, nti.ceantrib, nti.qcom, nti.vprod
            from tblnfeterceiroitem nti
            left join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
            left join tblprodutobarra pbs on (pbs.codprodutovariacao = pb.codprodutovariacao)
            where nti.codnfeterceiro = ?
            and pbs.barras = ?
            union
            select nti.codnfeterceiroitem, nti.cprod, nti.xprod, nti.cean, nti.ceantrib, nti.qcom, nti.vprod
            from tblnfeterceiroitem nti
            where nti.codnfeterceiro = ?
            and ? in (nti.cean, nti.ceantrib)
            union
            select nti.codnfeterceiroitem, nti.cprod, nti.xprod, nti.cean, nti.ceantrib, nti.qcom, nti.vprod
            from tblnfeterceiroitem nti
            where nti.codnfeterceiro = ?
            and cprod ilike ?
        ', [$codnfeterceiro, $barras, $codnfeterceiro, $barras, $codnfeterceiro, "%{$barras}%"]);
    }

    public static function atualizar(NfeTerceiroItem $item, array $data): NfeTerceiroItem
    {
        if (!empty($item->NfeTerceiro->codnotafiscal)) {
            abort(409, 'NFe já foi importada e seus itens não podem mais ser editados.');
        }

        $item->fill($data);

        // Recalcula preço unitário
        if (array_key_exists('vprod', $data) && array_key_exists('qcom', $data) && $item->qcom > 0) {
            $item->vuncom = round($item->vprod / $item->qcom, 6);
        }

        $item->save();

        return $item->fresh();
    }

    public static function alternarConferencia(NfeTerceiroItem $item): NfeTerceiroItem
    {
        if ($item->conferencia) {
            $item->conferencia = null;
            $item->codusuarioconferencia = null;
        } else {
            $item->conferencia = now();
            $item->codusuarioconferencia = Auth::id();
        }
        $item->save();

        // Cascata: se todos itens conferidos, marca cabeçalho
        $nft = $item->NfeTerceiro;
        $pendentes = $nft->NfeTerceiroItemS()->whereNull('conferencia')->count();
        if ($pendentes === 0) {
            $nft->conferencia = now();
            $nft->codusuarioconferencia = Auth::id();
            $nft->save();
        }

        return $item->fresh();
    }

    public static function marcarTipoProduto(NfeTerceiro $nft, int $codtipoproduto): NfeTerceiro
    {
        if (!empty($nft->codnotafiscal)) {
            abort(409, 'NFe já foi importada.');
        }

        DB::transaction(function () use ($codtipoproduto, $nft) {
            DB::update('
                update tblnfeterceiroitem
                set codprodutobarra = (
                        select min(pb.codprodutobarra)
                        from tblncm n
                        inner join tblproduto p on (p.codncm = n.codncm)
                        inner join tblprodutobarra pb on (pb.codproduto = p.codproduto)
                        where n.ncm = tblnfeterceiroitem.ncm
                        and p.codtipoproduto = ?
                    ),
                    complemento = null,
                    margem = null
                where tblnfeterceiroitem.codnfeterceiro = ?
            ', [$codtipoproduto, $nft->codnfeterceiro]);
        });

        return $nft->fresh();
    }

    public static function informarComplemento(NfeTerceiro $nft, ?float $valor): NfeTerceiro
    {
        if (!empty($nft->codnotafiscal)) {
            abort(409, 'NFe já foi importada.');
        }

        if (empty($valor)) {
            DB::update('
                update tblnfeterceiroitem
                set complemento = null
                where codnfeterceiro = ?
            ', [$nft->codnfeterceiro]);
        } else {
            DB::update('
                update tblnfeterceiroitem
                set complemento = round((? / n.valorprodutos) * vprod, 2)
                from tblnfeterceiro n
                where n.codnfeterceiro = tblnfeterceiroitem.codnfeterceiro
                and tblnfeterceiroitem.codnfeterceiro = ?
            ', [$valor, $nft->codnfeterceiro]);
        }

        return $nft->fresh();
    }

    public static function dividir(NfeTerceiroItem $item, int $parcelas): NfeTerceiro
    {
        $nft = $item->NfeTerceiro;

        if (!empty($nft->codnotafiscal)) {
            abort(409, 'NFe já foi importada e seus itens não podem mais ser divididos.');
        }

        $percentuais = match ($parcelas) {
            2 => [55, 45],
            3 => [39, 33, 28],
            4 => [30, 27, 23, 20],
            5 => [26, 23, 20, 17, 14],
            6 => [24, 20, 17, 15, 13, 11],
            10 => [15, 14, 13, 12, 11, 9, 8, 7, 6, 5],
        };

        DB::beginTransaction();
        try {
            // Multiplica nitem por 100 se necessário
            $stats = DB::selectOne('
                select max(nitem) as max, count(*) as qtd
                from tblnfeterceiroitem
                where codnfeterceiro = ?
            ', [$nft->codnfeterceiro]);

            if ($stats->max == $stats->qtd) {
                DB::update('
                    update tblnfeterceiroitem
                    set nitem = nitem * 100
                    where codnfeterceiro = ?
                ', [$nft->codnfeterceiro]);
                $item->refresh();
            }

            // Acumula totais das parcelas novas para calcular resto
            $acum = ['vuncom' => 0, 'vprod' => 0, 'vbc' => 0, 'vicms' => 0,
                'vbcst' => 0, 'vicmsst' => 0, 'ipivbc' => 0, 'ipivipi' => 0,
                'complemento' => 0, 'vdesc' => 0];

            for ($i = $parcelas - 1; $i >= 0; $i--) {
                $nitem = $item->nitem + $i;
                $percentual = $percentuais[$i] / 100;
                $sufixo = ' ' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '/' . str_pad($parcelas, 2, '0', STR_PAD_LEFT);

                if ($i === 0) {
                    // Original fica com o resto
                    $item->nitem = $nitem;
                    $item->cprod .= $sufixo;
                    $item->xprod .= $sufixo;
                    $item->vuncom -= $acum['vuncom'];
                    $item->vprod -= $acum['vprod'];
                    $item->vbc -= $acum['vbc'];
                    $item->vicms -= $acum['vicms'];
                    $item->vbcst -= $acum['vbcst'];
                    $item->vicmsst -= $acum['vicmsst'];
                    $item->ipivbc -= $acum['ipivbc'];
                    $item->ipivipi -= $acum['ipivipi'];
                    $item->complemento -= $acum['complemento'];
                    $item->vdesc -= $acum['vdesc'];
                    $item->save();
                } else {
                    $novo = $item->replicate();
                    $novo->nitem = $nitem;
                    $novo->cprod .= $sufixo;
                    $novo->xprod .= $sufixo;
                    $novo->vuncom = round($item->vuncom * $percentual, 6);
                    $novo->vprod = round($novo->vuncom * $novo->qcom, 2);
                    $novo->vbc = round($item->vbc * $percentual, 2);
                    $novo->vicms = round($item->vicms * $percentual, 2);
                    $novo->vbcst = round($item->vbcst * $percentual, 2);
                    $novo->vicmsst = round($item->vicmsst * $percentual, 2);
                    $novo->ipivbc = round($item->ipivbc * $percentual, 2);
                    $novo->ipivipi = round($item->ipivipi * $percentual, 2);
                    $novo->complemento = round(($item->complemento ?? 0) * $percentual, 2);
                    $novo->vdesc = round(($item->vdesc ?? 0) * $percentual, 2);
                    $novo->codprodutobarra = null;
                    $novo->save();

                    $acum['vuncom'] += $novo->vuncom;
                    $acum['vprod'] += $novo->vprod;
                    $acum['vbc'] += $novo->vbc;
                    $acum['vicms'] += $novo->vicms;
                    $acum['vbcst'] += $novo->vbcst;
                    $acum['vicmsst'] += $novo->vicmsst;
                    $acum['ipivbc'] += $novo->ipivbc;
                    $acum['ipivipi'] += $novo->ipivipi;
                    $acum['complemento'] += $novo->complemento;
                    $acum['vdesc'] += $novo->vdesc;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $nft->fresh();
    }

    public static function copiaDadosUltimaOcorrencia(NfeTerceiroItem $destino)
    {
        // Primeiro tenta verifica se ja foi dado entrada em uma nota daquele fornecedor com a mesma referencia
        $sql = '
            select nti.codprodutobarra, nti.margem, nti.complemento, nti.margem, nti.vprod
            from tblnfeterceiro nt
            inner join tblnfeterceiroitem nti on (nti.codnfeterceiro = nt.codnfeterceiro)
            where nt.cnpj = :cnpj
            and nti.cprod = :cprod
            and nti.codprodutobarra is not null
            and nti.codnfeterceiro != :codnfeterceiro
            order by nti.alteracao desc, nti.codnfeterceiroitem desc
            limit 1
        ';
        $origem = DB::select($sql, [
            'codnfeterceiro' => $destino->codnfeterceiro,
            'cnpj' => $destino->NFeTerceiro->cnpj,
            'cprod' => $destino->cprod,
        ]);

        // se encontrou copia amarracao com nosso cadastro de produto, margem e complemento
        if (count($origem) != 0) {
            $origem = (object)$origem[0];
            $destino->codprodutobarra = $origem->codprodutobarra;

            if (!empty($origem->margem)) {
                $destino->margem = $origem->margem;
            }

            if (floatval($origem->complemento) > 0) {
                $complemento = floatval($origem->complemento) / floatval($origem->vprod);
                $destino->complemento = $complemento * $destino->vprod;
            }
            return $destino;
        }

        // se nao encontrou, procura pelo codigo de barras / barras trib
        $pb = ProdutoService::buscaPorBarras($destino->cean);
        if ($pb == false) {
            $pb = ProdutoService::buscaPorBarras($destino->ceantrib);
        }
        if ($pb) {
            $destino->codprodutobarra = $pb->codprodutobarra;
        }

        return $destino;
    }
}
