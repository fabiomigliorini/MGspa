<?php

namespace Mg\Requisicao;

use Illuminate\Support\Facades\DB;
use Mg\Produto\ProdutoBarraService;

// Gerador de requisicao (TASK-258): a reposicao de uma loja montada marca por marca, olhando o
// que esta' zerado ou abaixo do minimo dela e o estoque das outras lojas. So' consulta: o que
// o usuario marca fica no rascunho do front, e o Gerar cria as requisicoes pelo
// RequisicaoService::criar, uma por loja que atende.
//
// As regras sao as do PDF de Sugestao de Transferencias, na loja que pede:
//   trabalha = maximo > 1
//   abaixo   = trabalha e saldo <= minimo
//   zerado   = trabalha e saldo <= 0
//   faltam   = soma de (maximo - saldo) das variacoes que contam
//   furo     = soma de (maximo - saldo) x preco de venda das variacoes que contam (R$: soma
//              qualquer unidade, por isso e' ele que ordena)
// "Contam" as abaixo do minimo, ou so' as zeradas com o filtro de urgencia "zerado". A marca
// e' a do produto.
class RequisicaoGeradorService
{
    const POR_PAGINA = 50;

    const SITUACAO_ABAIXO = 'abaixo';
    const SITUACAO_ZERADO = 'zerado';

    // filtro ABC: as letras que passam
    const ABC = [
        'A' => ['A'],
        'AB' => ['A', 'B'],
        'ABC' => ['A', 'B', 'C'],
    ];

    // o que conta nos contadores, pela urgencia escolhida
    protected static function conta(?string $situacao): string
    {
        return $situacao === self::SITUACAO_ZERADO
            ? 'v.maximo > 1 and v.saldo <= 0'
            : 'v.maximo > 1 and v.saldo <= v.minimo';
    }

    // As variacoes ativas com o minimo, o maximo e o saldo fisico da loja (0 quando a loja
    // nao tem a variacao). Os filtros sao do produto: marca, ABC e a busca (o motor do F1).
    protected static function variacoes(int $codestoquelocal, array $filtros, array &$bind): string
    {
        $bind['codestoquelocal'] = $codestoquelocal;
        $where = ['p.inativo is null', 'pv.inativo is null'];
        if (!empty($filtros['codmarca'])) {
            $where[] = 'p.codmarca = :codmarca';
            $bind['codmarca'] = (int) $filtros['codmarca'];
        }
        if (!empty($filtros['abc']) && isset(self::ABC[$filtros['abc']])) {
            $where[] = "p.abc in ('" . implode("','", self::ABC[$filtros['abc']]) . "')";
        }
        if (!empty($filtros['busca']) && $motor = ProdutoBarraService::sqlBusca($filtros['busca'])) {
            $where[] = "p.codproduto in (select b.codproduto from ({$motor['sql']}) b)";
            $bind += $motor['bind'];
        }
        return "
            select
                p.codproduto, p.codmarca, p.preco,
                pv.codprodutovariacao, pv.variacao,
                elpv.estoqueminimo, elpv.estoquemaximo,
                coalesce(elpv.estoqueminimo, 0) as minimo,
                coalesce(elpv.estoquemaximo, 0) as maximo,
                coalesce(s.saldoquantidade, 0) as saldo
            from tblproduto p
            inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
            left join tblestoquelocalprodutovariacao elpv on (
                elpv.codprodutovariacao = pv.codprodutovariacao
                and elpv.codestoquelocal = :codestoquelocal
            )
            left join tblestoquesaldo s on (
                s.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
                and s.fiscal = false
            )
            where " . implode(' and ', $where);
    }

    // Todas as marcas ativas, com o contador e o furo da loja. Com o ABC, so' as que tem
    // produto na letra; com a urgencia, so' as que tem variacao nela. A tela ordena e pesquisa.
    public static function marcas(int $codestoquelocal, array $filtros): array
    {
        $bind = [];
        $variacoes = static::variacoes($codestoquelocal, $filtros, $bind);
        $conta = static::conta($filtros['situacao'] ?? null);
        $where = ['m.inativo is null'];
        if (!empty($filtros['abc'])) {
            $where[] = 'a.codmarca is not null';
        }
        if (!empty($filtros['situacao'])) {
            $where[] = 'a.abaixo > 0';
        }
        $sql = "
            with v as ({$variacoes}),
            a as (
                select
                    v.codmarca,
                    count(*) filter (where v.maximo > 1) as trabalha,
                    count(*) filter (where {$conta}) as abaixo,
                    coalesce(sum((v.maximo - v.saldo) * v.preco) filter (where {$conta}), 0) as furo
                from v
                group by v.codmarca
            )
            select
                m.codmarca, m.marca,
                coalesce(a.trabalha, 0) as trabalha,
                coalesce(a.abaixo, 0) as abaixo,
                round(coalesce(a.furo, 0), 2) as furo
            from tblmarca m
            left join a on (a.codmarca = m.codmarca)
            where " . implode(' and ', $where) . "
            order by m.marca
        ";
        return DB::select($sql, $bind);
    }

    // Os produtos (da marca, ou de todas), um por linha, com as variacoes da loja num array.
    // Todos os ativos, inclusive os que a loja nao trabalha; com a urgencia, so' os que tem
    // variacao nela. Ordem alfabetica ou pelo furo, paginada.
    public static function produtos(int $codestoquelocal, array $filtros, int $pagina): array
    {
        $bind = [];
        $variacoes = static::variacoes($codestoquelocal, $filtros, $bind);
        $conta = static::conta($filtros['situacao'] ?? null);
        $where = empty($filtros['situacao']) ? 'true' : 'a.abaixo > 0';
        // whitelist -- nunca concatenar o que vem do request
        $ordem = ($filtros['ordem'] ?? null) === 'alfabetica'
            ? 'p.produto, p.codproduto'
            : 'a.furo desc, a.faltam desc, p.produto, p.codproduto';
        $offset = (max(1, $pagina) - 1) * self::POR_PAGINA;
        // pagina primeiro pelos agregados; o array das variacoes so' dos 50 da pagina
        $sql = "
            with v as ({$variacoes}),
            a as (
                select
                    v.codproduto,
                    count(*) filter (where v.maximo > 1) as trabalha,
                    count(*) filter (where {$conta}) as abaixo,
                    coalesce(sum(v.maximo - v.saldo) filter (where {$conta}), 0) as faltam,
                    coalesce(sum((v.maximo - v.saldo) * v.preco) filter (where {$conta}), 0) as furo
                from v
                group by v.codproduto
            ),
            pagina as (
                select
                    p.codproduto, p.produto, p.abc, um.sigla,
                    a.trabalha, a.abaixo, a.faltam, round(a.furo, 2) as furo,
                    row_number() over (order by {$ordem}) as posicao
                from a
                inner join tblproduto p on (p.codproduto = a.codproduto)
                inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
                where {$where}
                order by {$ordem}
                limit " . self::POR_PAGINA . " offset {$offset}
            )
            select
                pg.codproduto, pg.produto, pg.abc, pg.sigla,
                pg.trabalha, pg.abaixo, pg.faltam, pg.furo,
                json_agg(json_build_object(
                    'codprodutovariacao', v.codprodutovariacao,
                    'variacao', v.variacao,
                    'saldo', v.saldo,
                    'estoqueminimo', v.estoqueminimo,
                    'estoquemaximo', v.estoquemaximo,
                    'situacao', case
                        when v.maximo > 1 and v.saldo <= 0 then 'zerado'
                        when v.maximo > 1 and v.saldo <= v.minimo then 'abaixo'
                        else 'ok'
                    end
                ) order by v.variacao nulls first, v.codprodutovariacao) as variacoes
            from pagina pg
            inner join v on (v.codproduto = pg.codproduto)
            group by pg.codproduto, pg.produto, pg.abc, pg.sigla, pg.trabalha, pg.abaixo,
                pg.faltam, pg.furo, pg.posicao
            order by pg.posicao
        ";
        return array_map(function ($r) {
            $r->variacoes = json_decode($r->variacoes);
            return $r;
        }, DB::select($sql, $bind));
    }

    // Um produto para pedir: a embalagem de transferencia (o multiplo) e as variacoes ativas,
    // cada uma com as barras em que o item sai, a situacao na loja que pede e, por loja que
    // controla estoque (sem a de defeito), o saldo, o minimo, o maximo e o que a loja que pede
    // ja' pediu dela em reposicao ainda aberta (na unidade do produto).
    public static function produto(int $codproduto, int $codestoquelocal): array
    {
        $produto = DB::selectOne("
            select
                p.codproduto, p.produto, p.abc, p.preco,
                m.codmarca, m.marca,
                um.sigla, um.decimais,
                pe.codprodutoembalagem as codprodutoembalagemtransferencia,
                pe.quantidade as multiplo,
                ume.sigla as siglaembalagem
            from tblproduto p
            inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
            left join tblmarca m on (m.codmarca = p.codmarca)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = p.codprodutoembalagemtransferencia)
            left join tblunidademedida ume on (ume.codunidademedida = pe.codunidademedida)
            where p.codproduto = :codproduto
        ", ['codproduto' => $codproduto]);
        if (!$produto) {
            abort(404, 'Produto não encontrado!');
        }

        $linhas = DB::select("
            select
                pv.codprodutovariacao, pv.variacao,
                el.codestoquelocal,
                elpv.estoqueminimo, elpv.estoquemaximo,
                coalesce(s.saldoquantidade, 0) as saldo,
                (select pb.codprodutobarra from tblprodutobarra pb
                  where pb.codprodutovariacao = pv.codprodutovariacao
                  and pb.codprodutoembalagem = :codprodutoembalagem
                  order by pb.codprodutobarra limit 1) as codprodutobarraembalagem,
                (select pb.codprodutobarra from tblprodutobarra pb
                  where pb.codprodutovariacao = pv.codprodutovariacao
                  and pb.codprodutoembalagem is null
                  order by pb.codprodutobarra limit 1) as codprodutobarraunidade
            from tblprodutovariacao pv
            cross join tblestoquelocal el
            left join tblestoquelocalprodutovariacao elpv on (
                elpv.codprodutovariacao = pv.codprodutovariacao
                and elpv.codestoquelocal = el.codestoquelocal
            )
            left join tblestoquesaldo s on (
                s.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
                and s.fiscal = false
            )
            where pv.codproduto = :codproduto
            and pv.inativo is null
            and el.inativo is null
            and el.controlaestoque
            and not el.defeito
            order by pv.variacao nulls first, pv.codprodutovariacao, el.codestoquelocal
        ", [
            'codproduto' => $codproduto,
            'codprodutoembalagem' => $produto->codprodutoembalagemtransferencia ?? 0,
        ]);

        // o que a loja que pede ja' pediu de cada loja, em reposicao ainda aberta, inclusive a
        // que saiu para entrega e nao chegou (a nao atendida nao vem mais)
        $pedidos = [];
        foreach (DB::select("
            select
                pb.codprodutovariacao,
                r.codestoquelocalrequisitada as codestoquelocal,
                sum(ri.quantidade * coalesce(pe.quantidade, 1)) as pedido
            from tblrequisicao r
            inner join tblrequisicaoitem ri on (ri.codrequisicao = r.codrequisicao)
            inner join tblprodutobarra pb on (pb.codprodutobarra = ri.codprodutobarra)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            where r.codestoquelocalrequisitante = :codestoquelocal
            and r.destino = :destino
            and r.status in ('A', 'S', 'P', 'T', 'D', 'R')
            and not " . RequisicaoService::SQL_NAO_ATENDIDA . "
            and pb.codproduto = :codproduto
            group by pb.codprodutovariacao, r.codestoquelocalrequisitada
        ", [
            'codestoquelocal' => $codestoquelocal,
            'destino' => Requisicao::DESTINO_LOJA,
            'codproduto' => $codproduto,
        ]) as $p) {
            $pedidos[$p->codprodutovariacao][$p->codestoquelocal] = (float) $p->pedido;
        }

        $variacoes = [];
        foreach ($linhas as $l) {
            $v = &$variacoes[$l->codprodutovariacao];
            $v ??= [
                'codprodutovariacao' => $l->codprodutovariacao,
                'variacao' => $l->variacao,
                'codprodutobarraembalagem' => $l->codprodutobarraembalagem,
                'codprodutobarraunidade' => $l->codprodutobarraunidade,
                'situacao' => null,
                'lojas' => [],
            ];
            $v['lojas'][] = [
                'codestoquelocal' => $l->codestoquelocal,
                'saldo' => (float) $l->saldo,
                'estoqueminimo' => $l->estoqueminimo,
                'estoquemaximo' => $l->estoquemaximo,
                'pedido' => $pedidos[$l->codprodutovariacao][$l->codestoquelocal] ?? 0,
            ];
            if ($l->codestoquelocal == $codestoquelocal) {
                $v['situacao'] = static::situacao($l->saldo, $l->estoqueminimo, $l->estoquemaximo);
            }
            unset($v);
        }

        return [
            'produto' => $produto,
            'variacoes' => array_values($variacoes),
        ];
    }

    // a situacao de cada variacao na loja, para a prioridade que a revisao pre-seleciona
    public static function situacoes(int $codestoquelocal, array $codprodutovariacoes): array
    {
        if (empty($codprodutovariacoes)) {
            return [];
        }
        $linhas = DB::select("
            select
                pv.codprodutovariacao,
                coalesce(s.saldoquantidade, 0) as saldo,
                elpv.estoqueminimo, elpv.estoquemaximo
            from tblprodutovariacao pv
            left join tblestoquelocalprodutovariacao elpv on (
                elpv.codprodutovariacao = pv.codprodutovariacao
                and elpv.codestoquelocal = ?
            )
            left join tblestoquesaldo s on (
                s.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
                and s.fiscal = false
            )
            where pv.codprodutovariacao in (" . implode(',', array_fill(0, count($codprodutovariacoes), '?')) . ")
        ", array_merge([$codestoquelocal], array_map('intval', $codprodutovariacoes)));
        $ret = [];
        foreach ($linhas as $l) {
            $ret[$l->codprodutovariacao] = static::situacao($l->saldo, $l->estoqueminimo, $l->estoquemaximo);
        }
        return $ret;
    }

    // zerado, abaixo (do minimo) ou ok, com as regras do topo
    public static function situacao($saldo, $minimo, $maximo): string
    {
        $saldo = (float) $saldo;
        if ((float) $maximo <= 1) {
            return 'ok';
        }
        if ($saldo <= 0) {
            return self::SITUACAO_ZERADO;
        }
        return $saldo <= (float) $minimo ? self::SITUACAO_ABAIXO : 'ok';
    }
}
