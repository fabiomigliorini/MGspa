<?php

namespace Mg\Tributacao;

use Mg\NotaFiscal\NotaFiscalItemTributo;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;

class TributacaoService
{
    public static function recalcularTributosItem(NotaFiscalProdutoBarra $item): int
    {

        $idsMantidos = [];

        // 🔎 Busca regras aplicáveis
        $regras = TributacaoRegra::query()
            ->where(function ($q) use ($item) {
                $q->whereNull('codnaturezaoperacao')
                    ->orWhere('codnaturezaoperacao', $item->NotaFiscal->codnaturezaoperacao);
            })
            ->where(function ($q) use ($item) {
                $q->whereNull('codestadodestino')
                    ->orWhere('codestadodestino', $item->NotaFiscal->Pessoa->Cidade->codestado);
            })
            ->where(function ($q) use ($item) {
                $q->whereNull('codcidadedestino')
                    ->orWhere('codcidadedestino', $item->NotaFiscal->Pessoa->codcidade);
            })
            ->whereDate('vigenciainicio', '<=', $item->NotaFiscal->emissao)
            ->where(function ($q) use ($item) {
                $q->whereNull('vigenciafim')
                    ->orWhereDate('vigenciafim', '>=', $item->NotaFiscal->emissao);
            })
            ->get();

        foreach ($regras as $regra) {

            $valorItem = $item->valortotal;

            // 🔹 Cálculo da base
            if ($regra->basepercentual < 100) {
                $baseCalculada = round(
                    $valorItem * ($regra->basepercentual / 100),
                    2
                );

                $valorReducao = round(
                    $valorItem - $baseCalculada,
                    2
                );

                $percentualReducao = round(
                    100 - $regra->basepercentual,
                    4
                );
            } else {
                $baseCalculada = round($valorItem, 2);
                $valorReducao = null;
                $percentualReducao = null;
            }

            // 🔹 Valor do tributo
            $valorTributo = round(
                $baseCalculada * ($regra->aliquota / 100),
                2
            );

            // 🔹 Crédito
            $valorCredito = null;
            if ($regra->geracredito) {
                $valorCredito = $valorTributo;
            }

            // 🔹 Upsert controlado
            $itemTributo = NotaFiscalItemTributo::firstOrNew([
                'codnotafiscalprodutobarra' => $item->codnotafiscalprodutobarra,
                'codtributo'               => $regra->codtributo,
                'codentetributante'        => $regra->codentetributante,
            ]);

            $itemTributo->fill([
                'basereducaopercentual' => $percentualReducao,
                'basereducao'           => $valorReducao,
                'base'                  => $baseCalculada,
                'aliquota'              => $regra->aliquota,
                'valor'                 => $valorTributo,
                'cst'                   => $regra->cst,
                'cclasstrib'            => $regra->cclasstrib,
                'geracredito'           => $regra->geracredito,
                'valorcredito'          => $valorCredito,
                'beneficiocodigo'       => $regra->beneficiocodigo,
                'fundamentolegal'       => $regra->observacoes,
            ]);

            $itemTributo->save();

            $idsMantidos[] = $itemTributo->codnotafiscalitemtributo;
        }

        // 🧹 Remove tributos que não se aplicam mais
        if (!empty($idsMantidos)) {
            NotaFiscalItemTributo::where('codnotafiscalprodutobarra', $item->codnotafiscalprodutobarra)
                ->whereNotIn('codnotafiscalitemtributo', $idsMantidos)
                ->delete();
        }

        return count($idsMantidos);
    }
}
