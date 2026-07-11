<?php

namespace Mg\Safra;

use Mg\MgService;
use Mg\Contrato\Contrato;
use Mg\Contrato\ContratoFixacao;
use Mg\Fazenda\Plantio;
use Mg\Grao\MovimentoGrao;

class SafraService extends MgService
{
    /**
     * KPIs da safra (todos PRONTOS do backend). Comercial (só contratos VENDA):
     *   - contratado = Σ quantidade (barter incluso)
     *   - fixado = Σ sacas fixadas (US$ ou R$, não-barter) + quantidade CHEIA dos
     *     barters (não estão disponíveis pra fixar)
     *   - afixar = max(0, contratado − fixado)
     *   - preço médio R$ = LÍQUIDO/sc do firme (BRL cheio + parte travada das US$)
     *   - preço médio moeda estrangeira = BRUTO/sc do saldo ainda NÃO travado
     * Agronômico (plantios): área, expectativa, colhido, produção (com regra de 3),
     * produtividade (exp/ha e colhido/ha-colhido), progresso (ha colhido/área).
     *   - disponível = max(0, Σ produção dos plantios − contratado)
     */
    public static function resumoComercial($codsafra): array
    {
        $safra = Safra::with('Cultura')->findOrFail($codsafra);
        $pesosaca = (float) ($safra->Cultura->pesosaca ?? 60) ?: 60;

        // ===== Comercial (contratos de VENDA da safra) =====
        $contratos = Contrato::where('codsafra', $codsafra)
            ->where('operacao', 'VENDA')
            ->whereNull('inativo')
            ->withSum(['ContratoFixacaoS as fixadosc' => fn ($q) => $q->whereNull('inativo')], 'quantidade')
            ->get();

        $contratado = (float) $contratos->sum('quantidade'); // barter conta na quantidade
        $fixado = (float) $contratos->sum(
            fn ($c) => $c->barter ? (float) $c->quantidade : (float) $c->fixadosc,
        );
        $afixar = max(0, $contratado - $fixado);

        // Preço médio: itera as fixações ativas (VENDA, não-barter). Firme em R$ =
        // BRL cheio + parte travada das US$ (líquido); estrangeira = saldo bruto por moeda.
        $fixacoes = ContratoFixacao::query()
            ->join('tblcontrato as c', 'c.codcontrato', '=', 'tblcontratofixacao.codcontrato')
            ->join('tblmoeda as m', 'm.codmoeda', '=', 'tblcontratofixacao.codmoeda')
            ->where('c.codsafra', $codsafra)
            ->where('c.operacao', 'VENDA')
            ->whereNull('c.inativo')
            ->where('c.barter', false)
            ->whereNull('tblcontratofixacao.inativo')
            ->select(
                'tblcontratofixacao.quantidade',
                'tblcontratofixacao.preco',
                'tblcontratofixacao.liquidobrl',
                'tblcontratofixacao.totalmoeda',
                'tblcontratofixacao.saldomoeda',
                'm.iso as moedaiso',
            )
            ->get();

        $firmeLiq = 0.0;
        $firmeSacas = 0.0;
        $estrangeiras = []; // iso => ['saldo' => saldomoeda, 'sacas' => flutuantes]
        foreach ($fixacoes as $f) {
            $preco = (float) $f->preco;
            $firmeLiq += (float) $f->liquidobrl;
            if ($f->moedaiso === 'BRL') {
                $firmeSacas += (float) $f->quantidade;
                continue;
            }
            $firmeSacas += $preco > 0 ? ((float) $f->totalmoeda - (float) $f->saldomoeda) / $preco : 0;
            $saldo = (float) $f->saldomoeda;
            if ($saldo > 0.005) {
                $estrangeiras[$f->moedaiso] ??= ['saldo' => 0.0, 'sacas' => 0.0];
                $estrangeiras[$f->moedaiso]['saldo'] += $saldo;
                $estrangeiras[$f->moedaiso]['sacas'] += $preco > 0 ? $saldo / $preco : 0;
            }
        }
        $usd = $estrangeiras['USD'] ?? null;

        // ===== Agronômico (plantios da safra) =====
        $colhidoKg = MovimentoGrao::where('contatipo', 'PLANTIO')
            ->where('codsafra', $codsafra)
            ->whereNull('inativo')
            ->groupBy('codplantio')
            ->selectRaw('codplantio, SUM(liquido) as kg')
            ->pluck('kg', 'codplantio');

        $areaTotal = 0.0;
        $expTotal = 0.0;
        $colhidoTotal = 0.0;
        $hacolhidoTotal = 0.0;
        $producaoTotal = 0.0;
        foreach (Plantio::where('codsafra', $codsafra)->whereNull('inativo')->get() as $p) {
            $area = (float) $p->areaplantada;
            $exp = (float) $p->expectativasacas;
            $ha = (float) $p->hacolhido;
            $colhidoSc = (float) ($colhidoKg[$p->codplantio] ?? 0) / $pesosaca;
            $areaTotal += $area;
            $expTotal += $exp;
            $colhidoTotal += $colhidoSc;
            $hacolhidoTotal += $ha;
            $producaoTotal += static::producaoPlantio($area, $exp, $ha, $colhidoSc);
        }
        $disponivel = max(0, $producaoTotal - $contratado);

        return [
            'ncontratos' => $contratos->count(),
            // comercial
            'contratado' => round($contratado, 0),
            'fixado' => round($fixado, 0),
            'afixar' => round($afixar, 0),
            'disponivel' => round($disponivel, 0),
            'precomediobrl' => $firmeSacas > 0 ? round($firmeLiq / $firmeSacas, 2) : null, // R$ líquido/sc
            'precomediousd' => ($usd && $usd['sacas'] > 0) ? round($usd['saldo'] / $usd['sacas'], 2) : null,
            // agronômico
            'areaplantada' => round($areaTotal, 2),
            'expectativa' => round($expTotal, 0),
            'colhido' => round($colhidoTotal, 0),
            'producao' => round($producaoTotal, 0),
            'hacolhido' => round($hacolhidoTotal, 2),
            'produtividadeexpectativa' => $areaTotal > 0 ? round($expTotal / $areaTotal, 2) : null,
            'produtividadecolhido' => $hacolhidoTotal > 0 ? round($colhidoTotal / $hacolhidoTotal, 2) : null,
            'progressocolheita' => $areaTotal > 0 ? min(1, round($hacolhidoTotal / $areaTotal, 4)) : 0,
        ];
    }

    /**
     * Produção estimada de um plantio (sacas):
     *   - nada colhido (ha=0 ou colhido=0) → expectativa
     *   - finalizado (ha colhido ≥ área)   → colhido
     *   - colhendo → colhido + (colhido/ha) × (área − ha)  [regra de 3: projeta o
     *     restante pela produtividade REAL do que já colheu]
     */
    protected static function producaoPlantio(float $area, float $exp, float $ha, float $colhido): float
    {
        if ($ha <= 0 || $colhido <= 0) {
            return $exp;
        }
        if ($ha >= $area) {
            return $colhido;
        }
        return $colhido + ($colhido / $ha) * ($area - $ha);
    }

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Safra::query()->with('Cultura');

        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['safra'])) {
            $qry->palavras('safra', $filter['safra']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['-anoplantio', 'safra']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
