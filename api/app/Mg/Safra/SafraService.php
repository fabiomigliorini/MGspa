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
        $safra = Safra::with('Cultura')->findOrFail($codsafra);
        $pesosacaSafra = (float) ($safra->Cultura->pesosaca ?? 60) ?: 60;

        // Entregue = SUM(liquido) no extrato (carga inativada some, entao basta
        // somar os movimentos ativos). So contratos de VENDA entram no comercial.
        $movAtivo = fn ($q) => $q->whereNull('inativo');

        $contratos = Contrato::where('codsafra', $codsafra)
            ->where('operacao', 'VENDA')
            ->whereNull('inativo')
            ->with('Cultura')
            ->withSum(['MovimentoGraoS as carregadokg' => $movAtivo], 'liquido') // KG entregue (extrato)
            ->withSum(['ContratoFixacaoS as fixado' => fn ($q) => $q->whereNull('inativo')], 'quantidade')
            // barter (settlement em insumos) vive no pagamento; exclui do fixo/afixar/preço médio.
            ->withCount(['ContratoPagamentoS as bartercount' => fn ($q) => $q->where('forma', 'BARTER')->whereNull('inativo')])
            ->get();
        $semBarter = fn ($c) => (int) $c->bartercount === 0;

        // Unidade de trabalho = KG. Contrato negocia em sacas; converte por
        // contrato (cada um pode ter cultura/pesosaca diferente). Sacas derivadas
        // tambem somadas por contrato (NAO entreguekg/pesosaca da safra).
        $pesosaca = fn ($c) => (float) ($c->Cultura->pesosaca ?? 60) ?: 60;
        $contratado = (float) $contratos->sum('quantidade');                       // sc negociadas
        $contratadokg = (float) $contratos->sum(fn ($c) => (float) $c->quantidade * $pesosaca($c));
        $entreguekg = (float) $contratos->sum('carregadokg');
        $entreguesc = (float) $contratos->sum(fn ($c) => (float) $c->carregadokg / $pesosaca($c));
        // Volume em aberto (quantidade NULL, rapa-silo) nao reserva saldo.
        $saldoaembarcarkg = (float) $contratos->sum(
            fn ($c) => $c->quantidade === null ? 0 : max(0, (float) $c->quantidade * $pesosaca($c) - (float) $c->carregadokg)
        );

        // Estoque depositado (silo proprio + terceiro + silo bag) = SUM(liquido)
        // das contas UNIDADE no extrato, desta safra.
        $estoquekg = (float) MovimentoGrao::where('contatipo', 'UNIDADE')
            ->where('codsafra', $codsafra)
            ->whereNull('inativo')
            ->sum('liquido');

        // A colher = expectativa em pe ainda nao colhida. Colhido por plantio =
        // SUM(liquido) das contas PLANTIO; floor 0 por plantio (excedente ja foi
        // pro estoque). expectativasacas -> kg pela pesosaca da cultura da safra.
        $colhidoPorPlantio = MovimentoGrao::where('contatipo', 'PLANTIO')
            ->where('codsafra', $codsafra)
            ->whereNull('inativo')
            ->groupBy('codplantio')
            ->selectRaw('codplantio, SUM(liquido) as kg')
            ->pluck('kg', 'codplantio');
        $aColherKg = (float) Plantio::where('codsafra', $codsafra)
            ->whereNull('inativo')
            ->get()
            ->sum(function ($p) use ($colhidoPorPlantio, $pesosacaSafra) {
                $expectativakg = (float) $p->expectativasacas * $pesosacaSafra;
                $colhido = (float) ($colhidoPorPlantio[$p->codplantio] ?? 0);
                return max(0, $expectativakg - $colhido);
            });

        // Disponivel para negociar = a colher + estoque - (contratado venda nao entregue).
        $disponivelkg = $aColherKg + $estoquekg - $saldoaembarcarkg;

        // fixo = sacas já travadas (não-barter); afixar = saldo a fixar dos com teto
        // (FIXO fica 0 naturalmente, pois fixado cobre a quantidade).
        $fixo = (float) $contratos->filter($semBarter)->sum('fixado');
        $afixar = (float) $contratos->filter($semBarter)
            ->sum(fn ($c) => $c->quantidade === null ? 0 : max(0, (float) $c->quantidade - (float) $c->fixado));

        // Preço médio ponderado por moeda (fixações ativas de contratos ativos,
        // exceto barter).
        $buckets = ContratoFixacao::query()
            ->join('tblcontrato as c', 'c.codcontrato', '=', 'tblcontratofixacao.codcontrato')
            ->where('c.codsafra', $codsafra)
            ->where('c.operacao', 'VENDA')
            ->whereNull('c.inativo')
            // exclui contratos barter (têm pagamento forma=BARTER)
            ->whereNotExists(fn ($q) => $q->from('tblcontratopagamento as p')
                ->whereColumn('p.codcontrato', 'c.codcontrato')
                ->where('p.forma', 'BARTER')
                ->whereNull('p.inativo'))
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
            'entreguekg' => $entreguekg,                 // kg entregue (extrato)
            'entreguesc' => round($entreguesc, 2),       // sacas derivadas (por contrato)
            'saldoaembarcarkg' => $saldoaembarcarkg,     // kg a embarcar (volume em aberto = 0)
            'estoquekg' => round($estoquekg, 3),         // estoque depositado (silo proprio + terceiro)
            'estoquesc' => round($estoquekg / $pesosacaSafra, 2),
            'acolherkg' => round($aColherKg, 3),         // expectativa em pe nao colhida
            'disponivelkg' => round($disponivelkg, 3),   // a colher + estoque - saldo a entregar
            'disponivelsc' => round($disponivelkg / $pesosacaSafra, 2),
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
