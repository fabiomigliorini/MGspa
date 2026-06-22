<?php

namespace Mg\Fazenda;

use Mg\MgService;
use Mg\Safra\Safra;
use Mg\Grao\MovimentoGrao;

class FazendaService extends MgService
{
    // Resumo agregado da fazenda (KPIs do dash): nº de talhões, área total dos
    // talhões, área plantada, colhido (sacas) e produtividade média, mais a
    // quebra por safra. Espelha CulturaService::resumo, mas filtrando pelos
    // talhões da fazenda (tbltalhao → tblplantio → tblsafra). Calculado no banco
    // pra não depender das cargas estarem no cache offline do device.
    public static function resumo($codfazenda)
    {
        Fazenda::findOrFail($codfazenda);

        $ntalhoes = Talhao::where('codfazenda', $codfazenda)
            ->whereNull('inativo')
            ->count();

        $areatalhoes = (float) Talhao::where('codfazenda', $codfazenda)
            ->whereNull('inativo')
            ->sum('area');

        $safras = self::resumoSafras($codfazenda);

        $areaplantada = array_sum(array_column($safras, 'area'));
        $sacas = array_sum(array_column($safras, 'sacas'));

        return [
            'ntalhoes' => $ntalhoes,
            'areatalhoes' => $areatalhoes,
            'areaplantada' => $areaplantada,
            'sacas' => $sacas,
            'produtividade' => $areaplantada > 0 ? $sacas / $areaplantada : 0,
            'safras' => $safras,
        ];
    }

    // Quebra por safra das safras que têm plantio nesta fazenda (plantio.codfazenda).
    // Colhido = soma do líquido do extrato de grão por plantio (tblmovimentograo,
    // contatipo PLANTIO). Saca = peso da cultura da safra (fallback 60), pois a
    // fazenda pode misturar culturas.
    private static function resumoSafras($codfazenda)
    {
        $areaPorSafra = Plantio::where('codfazenda', $codfazenda)
            ->whereNull('inativo')
            ->groupBy('codsafra')
            ->selectRaw('codsafra, SUM(areaplantada) as area')
            ->pluck('area', 'codsafra');

        $colhidoPorSafra = MovimentoGrao::join('tblplantio as p', 'p.codplantio', '=', 'tblmovimentograo.codplantio')
            ->where('tblmovimentograo.contatipo', 'PLANTIO')
            ->where('p.codfazenda', $codfazenda)
            ->whereNull('tblmovimentograo.inativo')
            ->whereNull('p.inativo')
            ->groupBy('p.codsafra')
            ->selectRaw('p.codsafra, SUM(tblmovimentograo.liquido) as colhido')
            ->pluck('colhido', 'codsafra');

        $codsafras = $areaPorSafra->keys()->merge($colhidoPorSafra->keys())->unique();

        if ($codsafras->isEmpty()) {
            return [];
        }

        return Safra::with('Cultura')
            ->whereIn('codsafra', $codsafras)
            ->orderByDesc('anoplantio')
            ->get()
            ->map(function ($s) use ($areaPorSafra, $colhidoPorSafra) {
                $pesosaca = (float) ($s->Cultura->pesosaca ?? 60) ?: 60;
                $area = (float) ($areaPorSafra[$s->codsafra] ?? 0);
                $colhidokg = (float) ($colhidoPorSafra[$s->codsafra] ?? 0);
                $sacas = $pesosaca > 0 ? $colhidokg / $pesosaca : 0;
                return [
                    'codsafra' => $s->codsafra,
                    'safra' => $s->safra,
                    'area' => $area,
                    'colhidokg' => $colhidokg,
                    'sacas' => $sacas,
                    'produtividade' => $area > 0 ? $sacas / $area : 0,
                ];
            })
            ->all();
    }

    // Área total da fazenda = soma da área dos talhões ativos (layout base). Não
    // é digitada: recalculada e persistida em tblfazenda.areatotal sempre que um
    // talhão é criado/editado/inativado/excluído (ver Talhao::booted). Mantida
    // como coluna pra listagem (FazendasPage) não precisar somar no front.
    public static function recalcularAreaTotal($codfazenda)
    {
        if (!$codfazenda) {
            return;
        }
        $area = (float) Talhao::where('codfazenda', $codfazenda)
            ->whereNull('inativo')
            ->sum('area');
        Fazenda::where('codfazenda', $codfazenda)->update(['areatotal' => $area]);
    }

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Fazenda::query()->with('Pessoa');

        if (!empty($filter['codfazenda'])) {
            $qry->where('codfazenda', $filter['codfazenda']);
        }

        if (!empty($filter['fazenda'])) {
            $qry->palavras('fazenda', $filter['fazenda']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['fazenda']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
