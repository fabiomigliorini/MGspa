<?php

namespace Mg\Cultura;

use Mg\MgService;
use Mg\Safra\Safra;
use Mg\Safra\CargaColheita;
use Mg\Safra\CargaColheitaPlantio;
use Mg\Fazenda\Plantio;

class CulturaService extends MgService
{
    // Resumo agregado de todas as safras da cultura (KPIs do dash):
    // nº de safras, área plantada total, colhido (kg líquido seco e sacas) e
    // produtividade média (sc/ha). Calculado no banco pra não depender das
    // cargas estarem no cache offline do device.
    public static function resumo($codcultura)
    {
        $cultura = Cultura::findOrFail($codcultura);
        $pesosaca = (float) ($cultura->pesosaca ?: 60);

        $nsafras = Safra::where('codcultura', $codcultura)
            ->whereNull('inativo')
            ->count();

        $area = (float) Plantio::join('tblsafra', 'tblsafra.codsafra', '=', 'tblplantio.codsafra')
            ->where('tblsafra.codcultura', $codcultura)
            ->whereNull('tblplantio.inativo')
            ->whereNull('tblsafra.inativo')
            ->sum('tblplantio.areaplantada');

        $colhidokg = (float) CargaColheita::join('tblsafra', 'tblsafra.codsafra', '=', 'tblcargacolheita.codsafra')
            ->where('tblsafra.codcultura', $codcultura)
            ->where('tblcargacolheita.etapa', 'FINALIZADO')
            ->whereNull('tblcargacolheita.inativo')
            ->sum('tblcargacolheita.pesoliquidoseco');

        $sacas = $pesosaca > 0 ? $colhidokg / $pesosaca : 0;

        return [
            'nsafras' => $nsafras,
            'area' => $area,
            'colhidokg' => $colhidokg,
            'sacas' => $sacas,
            'produtividade' => $area > 0 ? $sacas / $area : 0,
            'variedades' => self::resumoVariedades($codcultura, $pesosaca),
        ];
    }

    // Quebra do resumo por variedade (todas as variedades ativas da cultura,
    // inclusive as sem colheita). Colhido rateado pelo percentual de cada
    // plantio na carga (tblcargacolheitaplantio).
    private static function resumoVariedades($codcultura, $pesosaca)
    {
        $areaPorVar = Plantio::join('tblsafra', 'tblsafra.codsafra', '=', 'tblplantio.codsafra')
            ->where('tblsafra.codcultura', $codcultura)
            ->whereNull('tblplantio.inativo')
            ->whereNull('tblsafra.inativo')
            ->groupBy('tblplantio.codvariedade')
            ->selectRaw('tblplantio.codvariedade, SUM(tblplantio.areaplantada) as area')
            ->pluck('area', 'codvariedade');

        $colhidoPorVar = CargaColheitaPlantio::join('tblcargacolheita as cc', 'cc.codcargacolheita', '=', 'tblcargacolheitaplantio.codcargacolheita')
            ->join('tblplantio as p', 'p.codplantio', '=', 'tblcargacolheitaplantio.codplantio')
            ->join('tblsafra as s', 's.codsafra', '=', 'p.codsafra')
            ->where('s.codcultura', $codcultura)
            ->where('cc.etapa', 'FINALIZADO')
            ->whereNull('cc.inativo')
            ->whereNull('p.inativo')
            ->whereNull('s.inativo')
            ->groupBy('p.codvariedade')
            ->selectRaw('p.codvariedade, SUM(cc.pesoliquidoseco * tblcargacolheitaplantio.percentual / 100) as colhido')
            ->pluck('colhido', 'codvariedade');

        return Variedade::where('codcultura', $codcultura)
            ->whereNull('inativo')
            ->orderBy('variedade')
            ->get(['codvariedade', 'variedade'])
            ->map(function ($v) use ($areaPorVar, $colhidoPorVar, $pesosaca) {
                $area = (float) ($areaPorVar[$v->codvariedade] ?? 0);
                $colhidokg = (float) ($colhidoPorVar[$v->codvariedade] ?? 0);
                $sacas = $pesosaca > 0 ? $colhidokg / $pesosaca : 0;
                return [
                    'codvariedade' => $v->codvariedade,
                    'variedade' => $v->variedade,
                    'area' => $area,
                    'colhidokg' => $colhidokg,
                    'sacas' => $sacas,
                    'produtividade' => $area > 0 ? $sacas / $area : 0,
                ];
            })
            ->all();
    }

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Cultura::query();

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['cultura'])) {
            $qry->palavras('cultura', $filter['cultura']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['cultura']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
