<?php

namespace Mg\Requisicao;

use Illuminate\Support\Facades\DB;
use Mg\Negocio\NegocioService;

// Processo idempotente da requisicao (TASK-55, doc-6): o unico que grava o atendido
// dos itens e o status. Roda a cada acao da requisicao, a cada evento dos negocios
// ligados (ProcessarRequisicaoJob) e no botao Reprocessar. Le as linhas dos negocios
// fechados ligados e distribui entre os itens; nada fica gravado no negocio.
class RequisicaoProcessamentoService
{
    // diferenca de quantidade que conta como igual
    const TOLERANCIA = 0.0005;

    // negocios ligados (ligacao ativa) que nao estao cancelados
    public static function negociosAtivos(Requisicao $req): array
    {
        return array_map(fn ($r) => (int) $r->codnegocio, DB::select('
            select nr.codnegocio
            from tblnegociorequisicao nr
            inner join tblnegocio n on (n.codnegocio = nr.codnegocio)
            where nr.codrequisicao = :codrequisicao
            and nr.inativo is null
            and n.codnegociostatus <> :cancelado
            order by nr.codnegocio
        ', ['codrequisicao' => $req->codrequisicao, 'cancelado' => NegocioService::STATUS_CANCELADO]));
    }

    // chamado pelo negocio nos eventos dele: so' dispara o processo, depois do commit
    public static function aoMudarNegocio(int $codnegocio, string $evento): void
    {
        $tem = DB::selectOne('select 1 as tem from tblnegociorequisicao where codnegocio = :codnegocio limit 1', [
            'codnegocio' => $codnegocio,
        ]);
        if ($tem) {
            ProcessarRequisicaoJob::dispatch($codnegocio, $evento)->afterCommit();
        }
    }

    // o evento do negocio: cancelado ou reaberto derrubam a finalizacao manual (as
    // justificativas dos itens ficam); depois reprocessa as requisicoes dele. Reprocessa
    // tambem as de ligacao desfeita (o desligar muda o atendido), mas so' a ligacao ativa
    // derruba a finalizacao: o negocio que ja' nao atende a requisicao nao mexe nela
    public static function processarEvento(int $codnegocio, string $evento): void
    {
        $ligacoes = DB::select(
            'select codrequisicao, inativo from tblnegociorequisicao where codnegocio = :codnegocio',
            ['codnegocio' => $codnegocio]
        );
        $codrequisicoes = array_values(array_unique(array_map(fn ($r) => (int) $r->codrequisicao, $ligacoes)));
        if (empty($codrequisicoes)) {
            return;
        }
        $ativas = array_values(array_unique(array_map(
            fn ($r) => (int) $r->codrequisicao,
            array_filter($ligacoes, fn ($r) => $r->inativo === null)
        )));
        DB::transaction(function () use ($codrequisicoes, $ativas, $evento) {
            if (in_array($evento, ['cancelado', 'reaberto']) && !empty($ativas)) {
                Requisicao::whereIn('codrequisicao', $ativas)
                    ->whereNotNull('finalizacao')
                    ->update(['finalizacao' => null, 'codusuariofinalizacao' => null]);
            }
            static::processarGrupo($codrequisicoes);
        });
    }

    public static function processar(Requisicao $req): Requisicao
    {
        static::processarGrupo([$req->codrequisicao]);
        return $req->refresh();
    }

    // As requisicoes que dividem negocios com estas (um negocio pode atender varias):
    // a distribuicao das linhas e' de todas juntas.
    public static function grupo(array $codrequisicoes): array
    {
        $grupo = array_values(array_unique(array_map('intval', $codrequisicoes)));
        do {
            $antes = count($grupo);
            $lista = implode(',', $grupo);
            $novas = DB::select("
                select distinct nr2.codrequisicao
                from tblnegociorequisicao nr
                inner join tblnegociorequisicao nr2 on (nr2.codnegocio = nr.codnegocio and nr2.inativo is null)
                where nr.codrequisicao in ({$lista})
                and nr.inativo is null
            ");
            foreach ($novas as $n) {
                if (!in_array((int) $n->codrequisicao, $grupo)) {
                    $grupo[] = (int) $n->codrequisicao;
                }
            }
        } while (count($grupo) > $antes && count($grupo) < 500);
        return $grupo;
    }

    // Distribui as linhas dos negocios fechados entre os itens (nada e' gravado aqui).
    // Cada linha vai, na unidade base, para o item da mesma variacao (entre as
    // requisicoes ligadas aquele negocio) com a menor diferenca entre a quantidade da
    // linha e o que ainda falta nele; empate, a requisicao mais antiga. O que sobra da
    // linha depois de cobrir o que falta segue para o proximo item; sem item faltando,
    // vira excesso no mais antigo; sem item da variacao, fica fora do pedido.
    public static function calcular(array $codrequisicoes): array
    {
        $grupo = static::grupo($codrequisicoes);
        $lista = implode(',', $grupo);

        $requisicoes = [];
        foreach (DB::select("select codrequisicao, criacao, cancelamento, finalizacao, status
                from tblrequisicao where codrequisicao in ({$lista})") as $r) {
            $requisicoes[(int) $r->codrequisicao] = $r;
        }

        $itens = [];
        foreach (DB::select("
            select ri.codrequisicaoitem, ri.codrequisicao, ri.codprodutobarra, ri.quantidade,
                ri.motivo, ri.codprodutobarrasubstituto,
                pb.codprodutovariacao, coalesce(pe.quantidade, 1) as fator,
                coalesce(ume.sigla, ump.sigla) as sigla,
                pbs.codprodutovariacao as codprodutovariacaosubstituto,
                r.criacao
            from tblrequisicaoitem ri
            inner join tblrequisicao r on (r.codrequisicao = ri.codrequisicao)
            inner join tblprodutobarra pb on (pb.codprodutobarra = ri.codprodutobarra)
            inner join tblproduto p on (p.codproduto = pb.codproduto)
            inner join tblunidademedida ump on (ump.codunidademedida = p.codunidademedida)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            left join tblunidademedida ume on (ume.codunidademedida = pe.codunidademedida)
            left join tblprodutobarra pbs on (pbs.codprodutobarra = ri.codprodutobarrasubstituto)
            where ri.codrequisicao in ({$lista})
            order by r.criacao, ri.codrequisicao, ri.codrequisicaoitem
        ") as $i) {
            $itens[(int) $i->codrequisicaoitem] = [
                'codrequisicaoitem' => (int) $i->codrequisicaoitem,
                'codrequisicao' => (int) $i->codrequisicao,
                'codprodutovariacao' => (int) $i->codprodutovariacao,
                'codprodutovariacaosubstituto' => $i->codprodutovariacaosubstituto ? (int) $i->codprodutovariacaosubstituto : null,
                'motivo' => $i->motivo,
                'pedido' => (float) $i->quantidade * (float) $i->fator,
                'atendido' => 0.0,
                // menor embalagem envolvida (para mostrar "faltam 3 pacotes")
                'fator' => (float) $i->fator,
                'sigla' => $i->sigla,
            ];
        }

        // negocios ligados (ativos) e as requisicoes de cada um
        $negocios = [];
        foreach (DB::select("
            select nr.codnegocio, nr.codrequisicao, n.codnegociostatus, n.lancamento, n.entregue
            from tblnegociorequisicao nr
            inner join tblnegocio n on (n.codnegocio = nr.codnegocio)
            where nr.codrequisicao in ({$lista}) and nr.inativo is null
            order by n.lancamento, nr.codnegocio
        ") as $n) {
            $cod = (int) $n->codnegocio;
            $negocios[$cod] ??= [
                'codnegocio' => $cod,
                'codnegociostatus' => (int) $n->codnegociostatus,
                'lancamento' => $n->lancamento,
                'entregue' => $n->entregue,
                'requisicoes' => [],
            ];
            $negocios[$cod]['requisicoes'][] = (int) $n->codrequisicao;
        }

        $fora = [];
        $fechados = array_keys(array_filter($negocios, fn ($n) => $n['codnegociostatus'] == NegocioService::STATUS_FECHADO));
        if (!empty($fechados)) {
            $listaNegocios = implode(',', $fechados);
            foreach (DB::select("
                select npb.codnegocioprodutobarra, npb.codnegocio, npb.codprodutobarra, npb.quantidade,
                    pb.codprodutovariacao, coalesce(pe.quantidade, 1) as fator,
                    coalesce(ume.sigla, ump.sigla) as sigla
                from tblnegocioprodutobarra npb
                inner join tblnegocio n on (n.codnegocio = npb.codnegocio)
                inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
                inner join tblproduto p on (p.codproduto = pb.codproduto)
                inner join tblunidademedida ump on (ump.codunidademedida = p.codunidademedida)
                left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
                left join tblunidademedida ume on (ume.codunidademedida = pe.codunidademedida)
                where npb.codnegocio in ({$listaNegocios}) and npb.inativo is null
                order by n.lancamento, npb.codnegocio, npb.codnegocioprodutobarra
            ") as $l) {
                $doNegocio = $negocios[(int) $l->codnegocio]['requisicoes'];
                $candidatos = array_keys(array_filter($itens, fn ($i) => in_array($i['codrequisicao'], $doNegocio)
                    && $i['codprodutovariacao'] == (int) $l->codprodutovariacao));
                $resto = (float) $l->quantidade * (float) $l->fator;
                if (empty($candidatos)) {
                    // fora do pedido: fica na requisicao mais antiga do negocio
                    $codreq = min($doNegocio);
                    foreach ($doNegocio as $c) {
                        if ($requisicoes[$c]->criacao < $requisicoes[$codreq]->criacao) {
                            $codreq = $c;
                        }
                    }
                    $fora[$codreq][] = [
                        'codnegocio' => (int) $l->codnegocio,
                        'codprodutobarra' => (int) $l->codprodutobarra,
                        'codprodutovariacao' => (int) $l->codprodutovariacao,
                        'quantidade' => (float) $l->quantidade,
                        'sigla' => $l->sigla,
                    ];
                    continue;
                }
                while ($resto > static::TOLERANCIA) {
                    $escolhido = null;
                    $menor = null;
                    foreach ($candidatos as $c) {
                        $falta = $itens[$c]['pedido'] - $itens[$c]['atendido'];
                        if ($falta <= static::TOLERANCIA) {
                            continue;
                        }
                        $dif = abs($falta - $resto);
                        if ($menor === null || $dif < $menor - static::TOLERANCIA) {
                            $menor = $dif;
                            $escolhido = $c;
                        }
                    }
                    if ($escolhido === null) {
                        // ninguem faltando: excesso no item da requisicao mais antiga
                        $itens[$candidatos[0]]['atendido'] += $resto;
                        $itens[$candidatos[0]]['fator'] = min($itens[$candidatos[0]]['fator'], (float) $l->fator);
                        break;
                    }
                    $falta = $itens[$escolhido]['pedido'] - $itens[$escolhido]['atendido'];
                    $parte = min($falta, $resto);
                    $itens[$escolhido]['atendido'] += $parte;
                    if ((float) $l->fator < $itens[$escolhido]['fator']) {
                        $itens[$escolhido]['fator'] = (float) $l->fator;
                        $itens[$escolhido]['sigla'] = $l->sigla;
                    }
                    $resto -= $parte;
                }
            }
        }

        return compact('grupo', 'requisicoes', 'itens', 'negocios', 'fora');
    }

    // grava o atendido e o status de cada requisicao do grupo
    public static function processarGrupo(array $codrequisicoes): void
    {
        $calc = static::calcular($codrequisicoes);
        foreach ($calc['itens'] as $cod => $item) {
            DB::update('update tblrequisicaoitem set quantidadeatendida = :atendido
                where codrequisicaoitem = :cod and quantidadeatendida <> :atendido2', [
                'atendido' => round($item['atendido'], 3),
                'atendido2' => round($item['atendido'], 3),
                'cod' => $cod,
            ]);
        }
        foreach ($calc['requisicoes'] as $codreq => $r) {
            $status = static::status($codreq, $calc);
            if ($status !== $r->status) {
                Requisicao::whereKey($codreq)->update(['status' => $status]);
            }
        }
    }

    public static function status(int $codreq, array $calc): string
    {
        $r = $calc['requisicoes'][$codreq];
        if (!empty($r->cancelamento)) {
            return Requisicao::STATUS_CANCELADA;
        }
        $negocios = array_filter($calc['negocios'], fn ($n) => in_array($codreq, $n['requisicoes']));
        if (array_filter($negocios, fn ($n) => $n['codnegociostatus'] == NegocioService::STATUS_ABERTO)) {
            return Requisicao::STATUS_EM_SEPARACAO;
        }
        $fechados = array_filter($negocios, fn ($n) => $n['codnegociostatus'] == NegocioService::STATUS_FECHADO);
        if (empty($fechados) && empty($r->finalizacao)) {
            return Requisicao::STATUS_AGUARDANDO;
        }
        $itens = array_filter($calc['itens'], fn ($i) => $i['codrequisicao'] == $codreq);
        $bateu = !empty($fechados) && empty($calc['fora'][$codreq])
            && !array_filter($itens, fn ($i) => abs($i['pedido'] - $i['atendido']) > static::TOLERANCIA);
        if ($bateu) {
            $status = Requisicao::STATUS_TOTAL;
        } elseif (!empty($r->finalizacao)) {
            $status = Requisicao::STATUS_DIVERGENCIA;
        } else {
            return Requisicao::STATUS_PARCIAL;
        }
        if (!empty($fechados) && !array_filter($fechados, fn ($n) => empty($n['entregue']))) {
            return Requisicao::STATUS_ENTREGUE;
        }
        return $status;
    }
}
