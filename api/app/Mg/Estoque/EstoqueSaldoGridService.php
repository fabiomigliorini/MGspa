<?php

namespace Mg\Estoque;

use Mg\MgService;
use Illuminate\Support\Facades\DB;

/**
 * Grid de saldos de estoque com agrupamento dinâmico e drill-down.
 * Porta o EstoqueSaldo::totais() do legado MGLara.
 */
class EstoqueSaldoGridService extends MgService
{
    private static function config(): array
    {
        return [
            'secaoproduto' => ['cod' => 'sec.codsecaoproduto', 'nome' => 'sec.secaoproduto', 'grupo' => 'sec.codsecaoproduto, sec.secaoproduto', 'proximo' => 'familiaproduto', 'filtroProximo' => 'codsecaoproduto'],
            'familiaproduto' => ['cod' => 'fam.codfamiliaproduto', 'nome' => 'fam.familiaproduto', 'grupo' => 'fam.codfamiliaproduto, fam.familiaproduto', 'proximo' => 'grupoproduto', 'filtroProximo' => 'codfamiliaproduto'],
            'grupoproduto' => ['cod' => 'gru.codgrupoproduto', 'nome' => 'gru.grupoproduto', 'grupo' => 'gru.codgrupoproduto, gru.grupoproduto', 'proximo' => 'subgrupoproduto', 'filtroProximo' => 'codgrupoproduto'],
            'subgrupoproduto' => ['cod' => 'sub.codsubgrupoproduto', 'nome' => 'sub.subgrupoproduto', 'grupo' => 'sub.codsubgrupoproduto, sub.subgrupoproduto', 'proximo' => 'marca', 'filtroProximo' => 'codsubgrupoproduto'],
            'marca' => ['cod' => 'mar.codmarca', 'nome' => 'mar.marca', 'grupo' => 'mar.codmarca, mar.marca', 'proximo' => 'produto', 'filtroProximo' => 'codmarca'],
            'produto' => ['cod' => 'p.codproduto', 'nome' => 'p.produto', 'grupo' => 'p.codproduto, p.produto', 'proximo' => 'variacao', 'filtroProximo' => 'codproduto'],
            'variacao' => ['cod' => 'pv.codprodutovariacao', 'nome' => "p.produto || ' » ' || coalesce(pv.variacao, '{ Sem Variação }')", 'grupo' => 'pv.codprodutovariacao, pv.variacao, p.produto', 'proximo' => null, 'filtroProximo' => 'codprodutovariacao'],
        ];
    }

    public static function totais(string $agrupamento, string $valor, array $filtro): array
    {
        $cfgs = self::config();
        $c = $cfgs[$agrupamento] ?? $cfgs['secaoproduto'];
        $valorExpr = $valor === 'venda' ? 'es.saldoquantidade * p.preco' : 'es.saldovalor';

        $q = DB::table('tblestoquelocalprodutovariacao as elpv')
            ->join('tblprodutovariacao as pv', 'pv.codprodutovariacao', '=', 'elpv.codprodutovariacao')
            ->join('tblproduto as p', 'p.codproduto', '=', 'pv.codproduto')
            ->leftJoin('tblsubgrupoproduto as sub', 'sub.codsubgrupoproduto', '=', 'p.codsubgrupoproduto')
            ->leftJoin('tblgrupoproduto as gru', 'gru.codgrupoproduto', '=', 'sub.codgrupoproduto')
            ->leftJoin('tblfamiliaproduto as fam', 'fam.codfamiliaproduto', '=', 'gru.codfamiliaproduto')
            ->leftJoin('tblsecaoproduto as sec', 'sec.codsecaoproduto', '=', 'fam.codsecaoproduto')
            ->leftJoin('tblmarca as mar', 'mar.codmarca', '=', 'p.codmarca')
            ->join('tblestoquesaldo as es', 'es.codestoquelocalprodutovariacao', '=', 'elpv.codestoquelocalprodutovariacao')
            ->where('es.saldoquantidade', '!=', 0)
            ->selectRaw("
                sum(es.saldoquantidade) as saldoquantidade,
                sum({$valorExpr}) as saldovalor,
                sum(elpv.estoqueminimo) as estoqueminimo,
                sum(elpv.estoquemaximo) as estoquemaximo,
                es.fiscal as fiscal,
                {$c['cod']} as coditem,
                {$c['nome']} as item
            ")
            ->groupByRaw("es.fiscal, {$c['grupo']}")
            ->orderByRaw($agrupamento === 'variacao' ? 'p.produto, pv.variacao' : $c['nome']);

        self::aplicarFiltros($q, $filtro);

        $rows = $q->get();

        $itens = [];
        foreach ($rows as $r) {
            $key = $r->coditem;
            if (!isset($itens[$key])) {
                $itens[$key] = [
                    'coditem' => $r->coditem,
                    'item' => $r->item,
                    'proximo' => $c['proximo'],
                    'filtroProximo' => $c['filtroProximo'],
                    'fisico' => ['saldoquantidade' => 0, 'saldovalor' => 0, 'estoqueminimo' => 0, 'estoquemaximo' => 0],
                    'fiscal' => ['saldoquantidade' => 0, 'saldovalor' => 0],
                ];
            }
            $alvo = $r->fiscal ? 'fiscal' : 'fisico';
            $itens[$key][$alvo]['saldoquantidade'] += (float) $r->saldoquantidade;
            $itens[$key][$alvo]['saldovalor'] += (float) $r->saldovalor;
            if (!$r->fiscal) {
                $itens[$key]['fisico']['estoqueminimo'] += (float) $r->estoqueminimo;
                $itens[$key]['fisico']['estoquemaximo'] += (float) $r->estoquemaximo;
            }
        }

        return array_values($itens);
    }

    private static function aplicarFiltros($q, array $filtro): void
    {
        if (!empty($filtro['codsecaoproduto'])) {
            $q->where('fam.codsecaoproduto', $filtro['codsecaoproduto']);
        }
        if (!empty($filtro['codfamiliaproduto'])) {
            $q->where('gru.codfamiliaproduto', $filtro['codfamiliaproduto']);
        }
        if (!empty($filtro['codgrupoproduto'])) {
            $q->where('sub.codgrupoproduto', $filtro['codgrupoproduto']);
        }
        if (!empty($filtro['codsubgrupoproduto'])) {
            $q->where('p.codsubgrupoproduto', $filtro['codsubgrupoproduto']);
        }
        if (!empty($filtro['codproduto'])) {
            $q->where('pv.codproduto', $filtro['codproduto']);
        }
        if (!empty($filtro['codprodutovariacao'])) {
            $q->where('elpv.codprodutovariacao', $filtro['codprodutovariacao']);
        }
        if (!empty($filtro['codestoquelocal'])) {
            $q->where('elpv.codestoquelocal', $filtro['codestoquelocal']);
        }
        if (!empty($filtro['corredor'])) {
            $q->where('elpv.corredor', $filtro['corredor']);
        }
        if (!empty($filtro['prateleira'])) {
            $q->where('elpv.prateleira', $filtro['prateleira']);
        }
        if (!empty($filtro['coluna'])) {
            $q->where('elpv.coluna', $filtro['coluna']);
        }
        if (!empty($filtro['bloco'])) {
            $q->where('elpv.bloco', $filtro['bloco']);
        }
        if (!empty($filtro['codmarca'])) {
            $q->where(function ($q2) use ($filtro) {
                $q2->where('p.codmarca', $filtro['codmarca'])
                    ->orWhere('pv.codmarca', $filtro['codmarca']);
            });
        }

        if (!empty($filtro['saldo']) || !empty($filtro['minimo']) || !empty($filtro['maximo'])) {
            $q->whereIn('es.codestoquelocalprodutovariacao', function ($q2) use ($filtro) {
                $q2->select('es2.codestoquelocalprodutovariacao')
                    ->from('tblestoquesaldo as es2')
                    ->join('tblestoquelocalprodutovariacao as elpv2', 'elpv2.codestoquelocalprodutovariacao', '=', 'es2.codestoquelocalprodutovariacao')
                    ->where('es2.fiscal', false);

                if (!empty($filtro['minimo'])) {
                    $q2->whereRaw($filtro['minimo'] == -1
                        ? 'es2.saldoquantidade < elpv2.estoqueminimo'
                        : 'es2.saldoquantidade >= elpv2.estoqueminimo');
                }
                if (!empty($filtro['maximo'])) {
                    $q2->whereRaw($filtro['maximo'] == -1
                        ? 'es2.saldoquantidade <= elpv2.estoquemaximo'
                        : 'es2.saldoquantidade > elpv2.estoquemaximo');
                }
                if (!empty($filtro['saldo'])) {
                    $q2->whereRaw($filtro['saldo'] == -1
                        ? 'es2.saldoquantidade < 0'
                        : 'es2.saldoquantidade > 0');
                }
            });
        }
    }
}
