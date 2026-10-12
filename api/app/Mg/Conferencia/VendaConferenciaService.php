<?php

namespace Mg\Conferencia;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;

/**
 * Venda com diferenca (M9 doc-3): Σ pagamentos efetivados + Σ parcelas ≠
 * total. Nasce de correcao de lancamento (valor errado, registro indevido)
 * e vira pendencia da filial (painel) e item da tela Divergencias. Venda nao
 * fica com diferenca: ou e' pagamento ou e' duplicata; o conserto e' no
 * proprio negocio.
 */
class VendaConferenciaService
{
    // o que foi pago pela venda: pagamento de saida (contrario, devolucao)
    // entra negativo, como no fechar do PDV
    const SQL_PAGO = "
        coalesce((select sum(case
                when p.codpagamentoorigem is not null
                    or (p.codportadororigem is not null and p.codportadordestino is null)
                then -p.total else p.total end)
            from tblpagamento p
            where p.codnegocio = n.codnegocio and p.estado = 'E'), 0)
        + coalesce((select sum(np.valor) from tblnegocioparcela np where np.codnegocio = n.codnegocio and np.inativo is null), 0)
    ";

    // Vendas fechadas (natureza financeira) que nao fecham, inclusive a sem
    // nenhum pagamento (TASK-218). $de/$ate: o lancamento ($ate inclusive);
    // null = sem limite. O painel pede desde o go-live, em ordem de data; a
    // tela Divergencias, o historico todo, as mais recentes primeiro, ate'
    // $limite, e cada linha traz o total.
    public static function desbalanceadas(?array $filiais, ?Carbon $de = null, ?Carbon $ate = null, ?int $limite = null): array
    {
        $params = [];
        $where = 'n.codnegociostatus = ' . NegocioService::STATUS_FECHADO;
        if ($de) {
            $params['de'] = $de->copy()->startOfDay()->format('Y-m-d H:i:s');
            $where .= ' and n.lancamento >= :de';
        }
        if ($ate) {
            $params['ate'] = $ate->copy()->addDay()->startOfDay()->format('Y-m-d H:i:s');
            $where .= ' and n.lancamento < :ate';
        }
        if ($filiais !== null) {
            $marcas = ['null'];
            foreach (array_values($filiais) as $i => $f) {
                $params["f{$i}"] = $f;
                $marcas[] = ":f{$i}";
            }
            $where .= ' and n.codfilial in (' . implode(',', $marcas) . ')';
        }
        // com filtro, a conta venda a venda (SQL_PAGO) e' rapida; o
        // historico todo (~5 milhoes de vendas) so' roda em segundos com as
        // somas agrupadas de uma vez
        $with = '';
        $somas = '';
        $diferenca = 'round(n.valortotal - (' . static::SQL_PAGO . '), 2)';
        if (!$de && !$ate && $filiais === null) {
            $with = "
                with pago as (
                    select p.codnegocio, sum(case
                            when p.codpagamentoorigem is not null
                                or (p.codportadororigem is not null and p.codportadordestino is null)
                            then -p.total else p.total end) as pago
                    from tblpagamento p
                    where p.estado = 'E'
                    and p.codnegocio is not null
                    group by p.codnegocio
                ), parcela as (
                    select np.codnegocio, sum(np.valor) as parcelas
                    from tblnegocioparcela np
                    where np.inativo is null
                    group by np.codnegocio
                )
            ";
            $somas = '
                left join pago pg on (pg.codnegocio = n.codnegocio)
                left join parcela pc on (pc.codnegocio = n.codnegocio)
            ';
            $diferenca = 'round(n.valortotal - coalesce(pg.pago, 0) - coalesce(pc.parcelas, 0), 2)';
        }
        $total = $limite ? ', count(*) over () as total' : '';
        $ordem = $limite ? 'x.lancamento desc, x.codnegocio desc limit ' . (int) $limite : 'x.lancamento';
        return DB::select("
            {$with}
            select x.* {$total} from (
                select n.codnegocio, n.lancamento, n.codfilial, f.filial, n.codpessoa, pe.fantasia,
                    n.valortotal, {$diferenca} as diferenca
                from tblnegocio n
                inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
                {$somas}
                left join tblfilial f on (f.codfilial = n.codfilial)
                left join tblpessoa pe on (pe.codpessoa = n.codpessoa)
                where nat.financeiro
                and {$where}
            ) x
            where abs(x.diferenca) >= 0.01
            order by {$ordem}
        ", $params);
    }

    public static function detalhe(Negocio $negocio): array
    {
        $negocio->load(['Pessoa', 'Pdv', 'Filial', 'NegocioParcelaS.Titulo']);
        return [
            'codnegocio' => $negocio->codnegocio,
            'codfilial' => $negocio->codfilial,
            'filial' => optional($negocio->Filial)->filial,
            'codpessoa' => $negocio->codpessoa,
            'fantasia' => optional($negocio->Pessoa)->fantasia,
            'pdv' => optional($negocio->Pdv)->apelido,
            'lancamento' => $negocio->lancamento,
            'valortotal' => (float) $negocio->valortotal,
            'diferenca' => static::diferenca($negocio),
            'pagamentos' => ConferenciaPagamentoResource::collection(
                $negocio->PagamentoS()->orderBy('transacao')->orderBy('codpagamento')->get()
            ),
            'parcelas' => $negocio->NegocioParcelaS->map(fn ($np) => [
                'codnegocioparcela' => $np->codnegocioparcela,
                'condicao' => $np->condicao,
                'vencimento' => $np->vencimento,
                'valor' => (float) $np->valor,
                'codtitulo' => $np->codtitulo,
                'numero' => optional($np->Titulo)->numero,
            ])->values(),
        ];
    }

    // positivo = faltou pagar; negativo = pagou a mais
    public static function diferenca(Negocio $negocio): float
    {
        $r = DB::selectOne("
            select round(n.valortotal - (" . static::SQL_PAGO . "), 2) as diferenca
            from tblnegocio n where n.codnegocio = :codnegocio
        ", ['codnegocio' => $negocio->codnegocio]);
        return (float) $r->diferenca;
    }
}
