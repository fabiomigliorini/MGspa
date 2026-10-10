<?php

namespace Mg\Pagamento;

use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaService;
use Mg\Negocio\NegocioService;
use Mg\Portador\PortadorAutorizador;
use Mg\Portador\PortadorUsuario;
use Mg\Titulo\MovimentoTitulo;

/**
 * Pagamento = fato; amarracao = outra coisa (conceito do Fabio, 09/10/2026).
 *
 * O pagamento so' registra o dinheiro que andou. O que ele pagou fica nas
 * amarracoes: movimentos de titulo (baixa, vale, adiantamento) e a venda
 * (codnegocio, o pagamento inteiro, so' quando a venda esta' fechada).
 *
 * Pendente = o saldo do pagamento (pago - devolvido) nao bate com o que esta'
 * amarrado. E' uma conta, nao um status: desamarrar, cancelar a venda ou
 * devolver parte faz o pagamento aparecer sozinho.
 *
 * Fica de fora o que nao e' fato a amarrar: rascunho da venda aberta (P),
 * cancelado, devolucao (o efeito dela esta' no original), transferencia,
 * pagamento sem documento por motivo (taxa, tarifa, rendimento), acerto de
 * RH, meios internos (vale, compensacao, folha, permuta, perda) e o que e'
 * de antes do inicio do razao.
 */
class PagamentoPendenciaService
{
    // meios que nao sao dinheiro: nao ficam pendentes
    const MEIOS_INTERNOS = [
        PagamentoService::MEIO_VALE,
        PagamentoService::MEIO_COMPENSACAO,
        PagamentoService::MEIO_FOLHA,
        PagamentoService::MEIO_PERMUTA,
        PagamentoService::MEIO_PERDA,
    ];

    // movimentos de titulo que amarram (baixa, vale, adiantamento) e nao
    // foram estornados
    public static function movimentosAtivos(Pagamento $pag)
    {
        return MovimentoTitulo::where('codpagamento', $pag->codpagamento)
            ->where('codtipomovimentotitulo', '<', 900)
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('tblmovimentotitulo as e')
                ->whereColumn('e.codmovimentotituloestorno', 'tblmovimentotitulo.codmovimentotitulo'))
            ->get();
    }

    // pago - devolvido (cancelamento no cartao, devolucao de PIX)
    public static function saldo(Pagamento $pag): float
    {
        $devolvido = Pagamento::where('codpagamentoorigem', $pag->codpagamento)
            ->where('estado', PagamentoService::ESTADO_EFETIVADO)
            ->sum('total');
        return round(abs((float) $pag->total) - abs((float) $devolvido), 2);
    }

    // o que esta' amarrado: titulos + a venda fechada
    public static function amarrado(Pagamento $pag): float
    {
        $titulos = static::movimentosAtivos($pag)->sum(fn ($m) => abs((float) $m->total));
        $venda = 0;
        if (!empty($pag->codnegocio) && optional($pag->Negocio)->codnegociostatus == NegocioService::STATUS_FECHADO) {
            $venda = abs((float) $pag->total);
        }
        return round($titulos + $venda, 2);
    }

    // o que ainda da' para amarrar
    public static function livre(Pagamento $pag): float
    {
        return round(static::saldo($pag) - static::amarrado($pag), 2);
    }

    // venda aberta: o integrado dela esta' "em andamento", nao pendente
    public static function emAndamento(Pagamento $pag): bool
    {
        return !empty($pag->codnegocio)
            && optional($pag->Negocio)->codnegociostatus == NegocioService::STATUS_ABERTO;
    }

    // entra na conta de pendencia?
    public static function considerado(Pagamento $pag): bool
    {
        return $pag->estado == PagamentoService::ESTADO_EFETIVADO
            && !in_array((int) $pag->meio, static::MEIOS_INTERNOS)
            && empty($pag->codpagamentoorigem)
            && empty($pag->motivo)
            && empty($pag->codperiodocolaboradoracerto)
            && !(!empty($pag->codportadororigem) && !empty($pag->codportadordestino))
            && $pag->transacao && $pag->transacao->gte(ConferenciaService::inicio());
    }

    public static function pendente(Pagamento $pag): bool
    {
        return static::considerado($pag)
            && !static::emAndamento($pag)
            && abs(static::livre($pag)) > 0.005;
    }

    // Pendentes (tela "Pagamentos nao resolvidos" e a forma "Ja' recebido"
    // do wizard). $filtros: codpessoa, codfilial, sentido ('entrada' |
    // 'saida'), codpagamento, so' os portadores em que o usuario tem papel.
    // Cada linha: o pagamento, saldo, amarrado e livre.
    public static function listar(array $filtros = []): array
    {
        $binds = ['inicio' => ConferenciaService::inicio()->format('Y-m-d H:i:s')];
        $where = [];
        if (!empty($filtros['codpessoa'])) {
            $where[] = 'p.codpessoa = :codpessoa';
            $binds['codpessoa'] = (int) $filtros['codpessoa'];
        }
        if (!empty($filtros['codfilial'])) {
            $where[] = 'p.codfilial = :codfilial';
            $binds['codfilial'] = (int) $filtros['codfilial'];
        }
        if (!empty($filtros['codpagamento'])) {
            $where[] = 'p.codpagamento = :codpagamento';
            $binds['codpagamento'] = (int) $filtros['codpagamento'];
        }
        if (($filtros['sentido'] ?? null) === 'entrada') {
            $where[] = 'p.codportadordestino is not null';
        } elseif (($filtros['sentido'] ?? null) === 'saida') {
            $where[] = 'p.codportadororigem is not null';
        }
        $meus = PortadorAutorizador::codportadores(PortadorUsuario::PAPEL_DEPOSITANTE);
        if ($meus !== null) {
            if (empty($meus)) {
                return [];
            }
            $where[] = 'coalesce(p.codportadordestino, p.codportadororigem) in (' . implode(',', array_map('intval', $meus)) . ')';
        }
        $internos = implode(',', static::MEIOS_INTERNOS);
        $sql = "
            with base as (
                select
                    p.codpagamento,
                    abs(p.total) - coalesce((
                        select abs(sum(d.total)) from tblpagamento d
                        where d.codpagamentoorigem = p.codpagamento and d.estado = 'E'
                    ), 0) as saldo,
                    coalesce((
                        select sum(abs(m.total)) from tblmovimentotitulo m
                        where m.codpagamento = p.codpagamento
                          and m.codtipomovimentotitulo < 900
                          and not exists (
                              select 1 from tblmovimentotitulo e
                              where e.codmovimentotituloestorno = m.codmovimentotitulo
                          )
                    ), 0)
                    + case when n.codnegociostatus = " . NegocioService::STATUS_FECHADO . " then abs(p.total) else 0 end
                    as amarrado
                from tblpagamento p
                left join tblnegocio n on (n.codnegocio = p.codnegocio)
                where p.estado = 'E'
                  and p.meio not in ({$internos})
                  and p.codpagamentoorigem is null
                  and p.motivo is null
                  and p.codperiodocolaboradoracerto is null
                  and not (p.codportadororigem is not null and p.codportadordestino is not null)
                  and p.transacao >= :inicio
                  and coalesce(n.codnegociostatus, 0) != " . NegocioService::STATUS_ABERTO . "
                  " . (empty($where) ? '' : ' and ' . implode(' and ', $where)) . "
            )
            select codpagamento, saldo, amarrado, round(saldo - amarrado, 2) as livre
            from base
            where abs(saldo - amarrado) > 0.005
            order by codpagamento desc
            limit 500
        ";
        $linhas = collect(DB::select($sql, $binds))->keyBy('codpagamento');
        if ($linhas->isEmpty()) {
            return [];
        }
        $pags = Pagamento::with(PagamentoListaService::RELACOES)
            ->whereIn('codpagamento', $linhas->keys())
            ->orderBy('codpagamento', 'desc')
            ->get();
        return $pags->map(fn ($pag) => [
            'pagamento' => $pag,
            'saldo' => (float) $linhas[$pag->codpagamento]->saldo,
            'amarrado' => (float) $linhas[$pag->codpagamento]->amarrado,
            'livre' => (float) $linhas[$pag->codpagamento]->livre,
        ])->all();
    }

    // linhas para a tela e para a forma "Ja' recebido" do wizard
    public static function formatar(array $linhas): array
    {
        return array_map(function ($l) {
            $pag = $l['pagamento'];
            $portador = $pag->PortadorDestino ?? $pag->PortadorOrigem;
            return [
                'codpagamento' => (int) $pag->codpagamento,
                'uuid' => $pag->uuid,
                'meio' => $pag->meio,
                'meiodescricao' => PagamentoService::descricao($pag),
                'transacao' => $pag->transacao,
                'entrada' => !empty($pag->codportadordestino),
                'total' => (float) $pag->total,
                'saldo' => $l['saldo'],
                'amarrado' => $l['amarrado'],
                'livre' => $l['livre'],
                'codpessoa' => $pag->codpessoa,
                'pessoa' => optional($pag->Pessoa)->fantasia,
                'codportador' => optional($portador)->codportador,
                'portador' => optional($portador)->portador,
                'codfilial' => $pag->codfilial,
                'maquineta' => optional($pag->Maquineta)->apelido,
                'autorizacao' => $pag->autorizacao,
                'nsu' => $pag->nsu,
                'bandeira' => $pag->bandeira,
                'codnegocio' => $pag->codnegocio,
                'integrado' => $pag->ehIntegrado(),
                'codpdv' => $pag->codpdv,
                'pdv' => optional($pag->Pdv)->apelido,
            ];
        }, $linhas);
    }
}
