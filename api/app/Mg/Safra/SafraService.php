<?php

namespace Mg\Safra;

use Mg\MgService;
use Mg\Contrato\Contrato;
use Mg\Contrato\ContratoFixacao;

class SafraService extends MgService
{
    /**
     * KPIs comerciais da safra: rollup dos contratos ativos. Calculado no banco
     * (não depende do cache offline). O colhido/expectativa (produção) ficam no
     * front; aqui só o lado comercial.
     *
     * - contratado/entregue: somam todos os tipos (barter conta na quantidade).
     * - fixo: sacas com preço travado = FIXO + FIXAR (barter fora). Com a
     *   normalização, o FIXO entra via fixação-espelho.
     * - afixar: só dos FIXAR (FIXO já 100% fixado; barter fora).
     * - preço médio: ponderado por quantidade, separado por moeda (barter fora).
     *   R$ pelo precoreal; USD pelo preço em dólar + dólar médio travado.
     */
    public static function resumoComercial($codsafra): array
    {
        Safra::findOrFail($codsafra);

        $contratos = Contrato::where('codsafra', $codsafra)
            ->whereNull('inativo')
            ->with('Cultura')
            ->withSum('EmbarqueContratoS as carregadokg', 'quantidade') // KG fisico embarcado
            ->withSum(['ContratoFixacaoS as fixado' => fn ($q) => $q->whereNull('inativo')], 'quantidade')
            ->get();

        // Unidade de trabalho = KG. Contrato negocia em sacas; converte por
        // contrato (cada um pode ter cultura/pesosaca diferente). Sacas derivadas
        // tambem somadas por contrato (NAO entreguekg/pesosaca da safra).
        $pesosaca = fn ($c) => (float) ($c->Cultura->pesosaca ?? 60) ?: 60;
        $contratado = (float) $contratos->sum('quantidade');                       // sc negociadas
        $contratadokg = (float) $contratos->sum(fn ($c) => (float) $c->quantidade * $pesosaca($c));
        $entreguekg = (float) $contratos->sum('carregadokg');
        $entreguesc = (float) $contratos->sum(fn ($c) => (float) $c->carregadokg / $pesosaca($c));
        $saldoaembarcarkg = (float) $contratos->sum(
            fn ($c) => $c->semlimite ? 0 : max(0, (float) $c->quantidade * $pesosaca($c) - (float) $c->carregadokg)
        );
        $fixo = (float) $contratos->whereIn('tipo', ['FIXO', 'FIXAR'])->sum('fixado');
        $afixar = (float) $contratos->where('tipo', 'FIXAR')
            ->sum(fn ($c) => max(0, (float) $c->quantidade - (float) $c->fixado));

        // Preço médio ponderado por moeda (fixações ativas de contratos ativos,
        // exceto barter).
        $buckets = ContratoFixacao::query()
            ->join('tblcontrato as c', 'c.codcontrato', '=', 'tblcontratofixacao.codcontrato')
            ->where('c.codsafra', $codsafra)
            ->whereNull('c.inativo')
            ->where('c.tipo', '!=', 'BARTER')
            ->whereNull('tblcontratofixacao.inativo')
            ->groupBy('tblcontratofixacao.moeda')
            ->selectRaw('tblcontratofixacao.moeda as moeda')
            ->selectRaw('SUM(tblcontratofixacao.quantidade) as q')
            ->selectRaw('SUM(tblcontratofixacao.precoreal * tblcontratofixacao.quantidade) as vreal')
            ->selectRaw('SUM(tblcontratofixacao.preco * tblcontratofixacao.quantidade) as vmoeda')
            ->selectRaw('SUM(tblcontratofixacao.dolar * tblcontratofixacao.quantidade) as vdolar')
            ->selectRaw('SUM(CASE WHEN tblcontratofixacao.dolar IS NOT NULL THEN tblcontratofixacao.quantidade ELSE 0 END) as qdolar')
            ->get()
            ->keyBy('moeda');

        $brl = $buckets->get('BRL');
        $usd = $buckets->get('USD');

        return [
            'ncontratos' => $contratos->count(),
            'contratado' => $contratado,                 // sc negociadas
            'contratadokg' => $contratadokg,             // kg = sc x pesosaca
            'entreguekg' => $entreguekg,                 // kg fisico embarcado
            'entreguesc' => round($entreguesc, 2),       // sacas derivadas (por contrato)
            'saldoaembarcarkg' => $saldoaembarcarkg,     // kg a embarcar (semlimite = 0)
            'fixo' => $fixo,
            'afixar' => $afixar,
            'precomediobrl' => ($brl && $brl->q > 0) ? (float) $brl->vreal / (float) $brl->q : null,
            'precomediousd' => ($usd && $usd->q > 0) ? (float) $usd->vmoeda / (float) $usd->q : null,
            'dolarmedio' => ($usd && $usd->qdolar > 0) ? (float) $usd->vdolar / (float) $usd->qdolar : null,
        ];
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
