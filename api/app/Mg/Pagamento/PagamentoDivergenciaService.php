<?php

namespace Mg\Pagamento;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Conferencia\VendaConferenciaService;
use Mg\Negocio\NegocioService;

/**
 * Divergencias (TASK-218): o dinheiro que nao bate com o que ele pagou.
 * Regra do Fabio (11/10/2026), sem excecao: o pagamento bate com a soma das
 * linhas dele na tblmovimentotitulo (todas, estornos inclusive; no acerto de
 * RH, mais as rubricas do acerto) ou com a venda. A venda fechada se confere
 * pela venda (pagamentos + parcelas = total, VendaConferenciaService); o
 * pagamento dela sem baixa fica so' por la'. O valor do pagamento e' o pago
 * menos o devolvido. Historico todo; data e filial restringem. Acesso pela
 * regra do painel (ConferenciaAutorizador).
 */
class PagamentoDivergenciaService
{
    const CASO_VENDA = 'venda';
    const CASO_BAIXAS = 'baixas';
    const CASO_SEM_DOCUMENTO = 'sem';
    const CASO_VENDA_ABERTA = 'aberta';
    const CASO_VENDA_CANCELADA = 'cancelada';

    const CASOS = [
        self::CASO_VENDA => 'Venda que não fecha',
        self::CASO_BAIXAS => 'Pagamento que não bate com as baixas',
        self::CASO_SEM_DOCUMENTO => 'Pagamento sem venda nem título',
        self::CASO_VENDA_ABERTA => 'Pagamento em venda aberta',
        self::CASO_VENDA_CANCELADA => 'Pagamento em venda cancelada',
    ];

    // a tela recebe as mais recentes, ate' aqui (o total vem junto)
    const LIMITE = 5000;

    // $filtros: caso, data_de, data_ate, codfilial
    public static function listar(array $filtros): array
    {
        $filiais = ConferenciaAutorizador::filiais();
        if ($filiais === []) {
            abort(403, 'Só Financeiro, Administrador ou Gerente!');
        }
        if (!empty($filtros['codfilial'])) {
            ConferenciaAutorizador::autorizar((int) $filtros['codfilial']);
            $filiais = [(int) $filtros['codfilial']];
        }
        $de = !empty($filtros['data_de']) ? Carbon::parse($filtros['data_de']) : null;
        $ate = !empty($filtros['data_ate']) ? Carbon::parse($filtros['data_ate']) : null;
        $casos = !empty($filtros['caso']) && isset(static::CASOS[$filtros['caso']])
            ? [$filtros['caso']]
            : array_keys(static::CASOS);

        $total = 0;
        $itens = [];
        if (in_array(static::CASO_VENDA, $casos)) {
            foreach (VendaConferenciaService::desbalanceadas($filiais, $de, $ate, static::LIMITE) as $v) {
                $total = (int) $v->total;
                $itens[] = [
                    'tipo' => 'venda',
                    'caso' => static::CASO_VENDA,
                    'id' => (int) $v->codnegocio,
                    'data' => $v->lancamento,
                    'codfilial' => $v->codfilial,
                    'filial' => $v->filial,
                    'codpessoa' => $v->codpessoa,
                    'fantasia' => $v->fantasia,
                    'valor' => (float) $v->valortotal,
                    // positivo = faltou pagar; negativo = pagou a mais
                    'diferenca' => (float) $v->diferenca,
                    'meio' => null,
                    'codnegocio' => (int) $v->codnegocio,
                ];
            }
        }
        $casosPagamento = array_values(array_diff($casos, [static::CASO_VENDA]));
        if (!empty($casosPagamento)) {
            $pags = static::pagamentos($filiais, $de, $ate, $casosPagamento);
            $total += (int) ($pags[0]->total ?? 0);
            foreach ($pags as $p) {
                $itens[] = [
                    'tipo' => 'pagamento',
                    'caso' => $p->caso,
                    'id' => (int) $p->codpagamento,
                    'data' => $p->transacao,
                    'codfilial' => $p->codfilial,
                    'filial' => $p->filial,
                    'codpessoa' => $p->codpessoa,
                    'fantasia' => $p->fantasia,
                    'valor' => (float) $p->valor,
                    // positivo = sobrou no pagamento (baixas a menos);
                    // negativo = as baixas passaram do pagamento
                    'diferenca' => round((float) $p->valor - (float) $p->quitado, 2),
                    'meio' => PagamentoService::MEIOS[$p->meio] ?? null,
                    'codnegocio' => $p->codnegocio ? (int) $p->codnegocio : null,
                ];
            }
        }
        usort($itens, fn ($a, $b) => strcmp((string) $b['data'], (string) $a['data']));
        return [
            'data' => array_slice($itens, 0, static::LIMITE),
            'total' => $total,
            'limite' => static::LIMITE,
        ];
    }

    // Pagamentos que nao batem, os mais recentes primeiro (ate' o LIMITE),
    // com o total em cada linha. Tres partes, so' as dos casos pedidos: com
    // baixa (a soma das linhas contra o valor); sem baixa e sem venda; sem
    // baixa em venda aberta ou cancelada. Sem baixa, nada foi pago (fora as
    // rubricas do acerto): diverge se o valor nao e' zero.
    private static function pagamentos(?array $filiais, ?Carbon $de, ?Carbon $ate, array $casos): array
    {
        $params = [];
        // o mesmo filtro em cada parte, com os proprios parametros (o PDO do
        // pgsql nao repete o nome)
        $filtro = function (string $sufixo) use ($filiais, $de, $ate, &$params): string {
            $where = "p.estado = '" . PagamentoService::ESTADO_EFETIVADO . "' and p.codpagamentoorigem is null";
            if ($de) {
                $params["de{$sufixo}"] = $de->copy()->startOfDay()->format('Y-m-d H:i:s');
                $where .= " and p.transacao >= :de{$sufixo}";
            }
            if ($ate) {
                $params["ate{$sufixo}"] = $ate->copy()->addDay()->startOfDay()->format('Y-m-d H:i:s');
                $where .= " and p.transacao < :ate{$sufixo}";
            }
            if ($filiais !== null) {
                $marcas = ['null'];
                foreach (array_values($filiais) as $i => $f) {
                    $params["f{$sufixo}{$i}"] = $f;
                    $marcas[] = ":f{$sufixo}{$i}";
                }
                $where .= ' and p.codfilial in (' . implode(',', $marcas) . ')';
            }
            return $where;
        };
        $aberta = NegocioService::STATUS_ABERTO;
        $cancelada = NegocioService::STATUS_CANCELADO;
        // so' as partes dos casos pedidos
        $partes = [];
        $restrito = $de || $ate || $filiais !== null;
        if (in_array(static::CASO_BAIXAS, $casos)) {
            // com filtro, soma as linhas pagamento a pagamento; o historico
            // todo, agrupado de uma vez
            $linhas = $restrito
                ? 'cross join lateral (
                        select sum(mt.total) as soma, count(*) as qtd
                        from tblmovimentotitulo mt
                        where mt.codpagamento = p.codpagamento
                    ) m'
                : 'inner join (
                        select codpagamento, sum(total) as soma, count(*) as qtd
                        from tblmovimentotitulo
                        where codpagamento is not null
                        group by codpagamento
                    ) m on (m.codpagamento = p.codpagamento)';
            $partes[] = "
                select p.codpagamento, 'baixas' as caso, abs(m.soma + coalesce(a.rubricas, 0)) as quitado
                from tblpagamento p
                {$linhas}
                left join tblperiodocolaboradoracerto a on (a.codperiodocolaboradoracerto = p.codperiodocolaboradoracerto)
                where {$filtro('b')}
                and m.qtd > 0
            ";
        }
        if (in_array(static::CASO_SEM_DOCUMENTO, $casos)) {
            // o acerto de RH sem baixa paga as rubricas
            $partes[] = "
                select p.codpagamento, 'sem' as caso, abs(coalesce(a.rubricas, 0)) as quitado
                from tblpagamento p
                left join tblperiodocolaboradoracerto a on (a.codperiodocolaboradoracerto = p.codperiodocolaboradoracerto)
                where {$filtro('s')}
                and p.codnegocio is null
                and not exists (select 1 from tblmovimentotitulo m where m.codpagamento = p.codpagamento)
            ";
        }
        $status = array_keys(array_filter([
            $aberta => in_array(static::CASO_VENDA_ABERTA, $casos),
            $cancelada => in_array(static::CASO_VENDA_CANCELADA, $casos),
        ]));
        if (!empty($status)) {
            $partes[] = "
                select p.codpagamento, case when n.codnegociostatus = {$aberta} then 'aberta' else 'cancelada' end as caso,
                    0 as quitado
                from tblpagamento p
                inner join tblnegocio n on (n.codnegocio = p.codnegocio)
                where {$filtro('v')}
                and n.codnegociostatus in (" . implode(',', $status) . ")
                and not exists (select 1 from tblmovimentotitulo m where m.codpagamento = p.codpagamento)
            ";
        }
        $base = implode(' union all ', $partes);
        $limite = (int) static::LIMITE;
        return DB::select("
            with devolvido as (
                select d.codpagamentoorigem, abs(sum(d.total)) as devolvido
                from tblpagamento d
                where d.codpagamentoorigem is not null
                and d.estado = 'E'
                group by d.codpagamentoorigem
            )
            select x.*, count(*) over () as total from (
                select b.codpagamento, b.caso, b.quitado,
                    abs(p.total) - coalesce(d.devolvido, 0) as valor,
                    p.transacao, p.meio, p.codnegocio, p.codfilial, f.filial,
                    pe.codpessoa, pe.fantasia
                from ({$base}) b
                inner join tblpagamento p on (p.codpagamento = b.codpagamento)
                left join devolvido d on (d.codpagamentoorigem = b.codpagamento)
                left join tblnegocio n on (n.codnegocio = p.codnegocio)
                left join tblfilial f on (f.codfilial = p.codfilial)
                left join tblpessoa pe on (pe.codpessoa = coalesce(p.codpessoa, n.codpessoa))
            ) x
            where abs(x.valor - x.quitado) > 0.005
            order by x.transacao desc, x.codpagamento desc
            limit {$limite}
        ", $params);
    }
}
