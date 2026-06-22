<?php

namespace Mg\Cultura;

use Mg\MgService;
use Mg\Safra\Safra;
use Mg\Grao\MovimentoGrao;
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

        // Colhido = SUM(liquido) das contas PLANTIO no extrato (so cargas
        // finalizadas/ativas geram movimento), das safras desta cultura.
        $colhidokg = (float) MovimentoGrao::join('tblsafra', 'tblsafra.codsafra', '=', 'tblmovimentograo.codsafra')
            ->where('tblmovimentograo.contatipo', 'PLANTIO')
            ->where('tblsafra.codcultura', $codcultura)
            ->whereNull('tblmovimentograo.inativo')
            ->sum('tblmovimentograo.liquido');

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
    // inclusive as sem colheita). Colhido = soma do líquido do extrato de grão
    // por plantio (tblmovimentograo, contatipo PLANTIO).
    private static function resumoVariedades($codcultura, $pesosaca)
    {
        $areaPorVar = Plantio::join('tblsafra', 'tblsafra.codsafra', '=', 'tblplantio.codsafra')
            ->where('tblsafra.codcultura', $codcultura)
            ->whereNull('tblplantio.inativo')
            ->whereNull('tblsafra.inativo')
            ->groupBy('tblplantio.codvariedade')
            ->selectRaw('tblplantio.codvariedade, SUM(tblplantio.areaplantada) as area')
            ->pluck('area', 'codvariedade');

        $colhidoPorVar = MovimentoGrao::join('tblplantio as p', 'p.codplantio', '=', 'tblmovimentograo.codplantio')
            ->join('tblsafra as s', 's.codsafra', '=', 'p.codsafra')
            ->where('tblmovimentograo.contatipo', 'PLANTIO')
            ->where('s.codcultura', $codcultura)
            ->whereNull('tblmovimentograo.inativo')
            ->whereNull('p.inativo')
            ->whereNull('s.inativo')
            ->groupBy('p.codvariedade')
            ->selectRaw('p.codvariedade, SUM(tblmovimentograo.liquido) as colhido')
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
