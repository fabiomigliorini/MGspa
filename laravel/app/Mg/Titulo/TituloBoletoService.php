<?php

namespace Mg\Titulo;

use Illuminate\Support\Facades\DB;

class TituloBoletoService
{
    private const LABELS_TIPOS_ABERTOS = [
        'vencidos'      => 'Vencidos',
        'vencer7'       => 'Vencer em 7 Dias',
        'vencer30'      => 'Vencer em 30 Dias',
        'vencer60'      => 'Vencer em 60 Dias',
        'vencermais60'  => 'Vencer mais de 60 Dias',
    ];

    public static function abertosResumo(): array
    {
        $sql = "
            select 1 as ordem, 'vencidos' as tipo, sum(tb.valoratual) as total, count(tb.codtituloboleto) as quantidade
            from tbltituloboleto tb
            where tb.estadotitulocobranca not in (6, 7)
              and tb.vencimento < date_trunc('day', now() - '1 day'::interval)
            union all
            select 2, 'vencer7', sum(tb.valoratual), count(tb.codtituloboleto)
            from tbltituloboleto tb
            where tb.estadotitulocobranca not in (6, 7)
              and tb.vencimento between date_trunc('day', now() - '1 day'::interval) and date_trunc('day', now() + '7 day'::interval)
            union all
            select 3, 'vencer30', sum(tb.valoratual), count(tb.codtituloboleto)
            from tbltituloboleto tb
            where tb.estadotitulocobranca not in (6, 7)
              and tb.vencimento between date_trunc('day', now() + '8 day'::interval) and date_trunc('day', now() + '30 day'::interval)
            union all
            select 4, 'vencer60', sum(tb.valoratual), count(tb.codtituloboleto)
            from tbltituloboleto tb
            where tb.estadotitulocobranca not in (6, 7)
              and tb.vencimento between date_trunc('day', now() + '31 day'::interval) and date_trunc('day', now() + '60 day'::interval)
            union all
            select 5, 'vencermais60', sum(tb.valoratual), count(tb.codtituloboleto)
            from tbltituloboleto tb
            where tb.estadotitulocobranca not in (6, 7)
              and tb.vencimento >= date_trunc('day', now() + '61 day'::interval)
            order by 1
        ";
        return array_map(function ($r) {
            return [
                'tipo'       => $r->tipo,
                'label'      => self::LABELS_TIPOS_ABERTOS[$r->tipo] ?? $r->tipo,
                'total'      => (float) ($r->total ?? 0),
                'quantidade' => (int) $r->quantidade,
            ];
        }, DB::select($sql));
    }

    public static function abertosLista(string $tipo): array
    {
        $corte = self::corteVencimento($tipo);
        if ($corte === null) {
            return [];
        }

        $sql = "
            select
                tb.codtituloboleto,
                tb.codtitulo,
                tb.codportador,
                tb.vencimento,
                tb.valoratual,
                tb.nossonumero,
                tb.estadotitulocobranca,
                tb.tipobaixatitulo,
                abs(t.saldo) as saldo,
                t.numero,
                t.codpessoa,
                p.fantasia,
                po.portador
            from tbltituloboleto tb
            inner join tblportador po on (po.codportador = tb.codportador)
            inner join tbltitulo t on (t.codtitulo = tb.codtitulo)
            inner join tblpessoa p on (p.codpessoa = t.codpessoa)
            where tb.estadotitulocobranca not in (6, 7)
              {$corte}
            order by tb.vencimento asc, tb.valoratual desc
        ";
        return array_map(fn ($r) => (array) $r, DB::select($sql));
    }

    private static function corteVencimento(string $tipo): ?string
    {
        switch ($tipo) {
            case 'vencidos':
                return "and tb.vencimento < date_trunc('day', now() - '1 day'::interval)";
            case 'vencer7':
                return "and tb.vencimento between date_trunc('day', now() - '1 day'::interval) and date_trunc('day', now() + '7 day'::interval)";
            case 'vencer30':
                return "and tb.vencimento between date_trunc('day', now() + '8 day'::interval) and date_trunc('day', now() + '30 day'::interval)";
            case 'vencer60':
                return "and tb.vencimento between date_trunc('day', now() + '31 day'::interval) and date_trunc('day', now() + '60 day'::interval)";
            case 'vencermais60':
                return "and tb.vencimento >= date_trunc('day', now() + '61 day'::interval)";
        }
        return null;
    }

    public static function liquidadosAnos(): array
    {
        $sql = "
            select
                extract('year' from tb.datacredito)::int as ano,
                sum(tb.valorpago) as total,
                count(*) as quantidade
            from tbltituloboleto tb
            where tb.datacredito is not null
            group by extract('year' from tb.datacredito)
            order by 1 desc
        ";
        return array_map(fn ($r) => [
            'ano'        => (string) $r->ano,
            'total'      => (float) ($r->total ?? 0),
            'quantidade' => (int) $r->quantidade,
        ], DB::select($sql));
    }

    public static function liquidadosMeses(string $ano): array
    {
        $sql = "
            select
                extract('month' from tb.datacredito)::int as mes,
                sum(tb.valorpago) as total,
                count(*) as quantidade
            from tbltituloboleto tb
            where extract('year' from tb.datacredito) = :ano
            group by extract('month' from tb.datacredito)
            order by 1 asc
        ";
        $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return array_map(fn ($r) => [
            'mes'        => str_pad((string) $r->mes, 2, '0', STR_PAD_LEFT),
            'label'      => $labels[$r->mes - 1] ?? (string) $r->mes,
            'total'      => (float) ($r->total ?? 0),
            'quantidade' => (int) $r->quantidade,
        ], DB::select($sql, ['ano' => $ano]));
    }

    public static function liquidadosDias(string $ano, string $mes): array
    {
        $sql = "
            select
                to_char(tb.datacredito, 'YYYY-MM-DD') as dia,
                sum(tb.valorpago) as total,
                count(*) as quantidade
            from tbltituloboleto tb
            where date_trunc('month', tb.datacredito) = :mes::date
            group by tb.datacredito
            order by tb.datacredito asc
        ";
        return array_map(fn ($r) => [
            'dia'        => $r->dia,
            'total'      => (float) ($r->total ?? 0),
            'quantidade' => (int) $r->quantidade,
        ], DB::select($sql, ['mes' => "{$ano}-{$mes}-01"]));
    }

    public static function liquidadosPortadores(string $dia): array
    {
        $sql = "
            select
                p.codportador,
                p.portador,
                coalesce(p.conta::varchar, p.codportador::varchar) || '-' || coalesce(p.contadigito::varchar, '0') as conta,
                sum(tb.valorpago) as total,
                count(*) as quantidade
            from tbltituloboleto tb
            inner join tblportador p on (p.codportador = tb.codportador)
            where tb.datacredito = :dia
            group by p.codportador, p.portador, p.conta, p.contadigito
            order by p.portador asc
        ";
        return array_map(fn ($r) => [
            'codportador' => (int) $r->codportador,
            'portador'    => $r->portador,
            'conta'       => $r->conta,
            'total'       => (float) ($r->total ?? 0),
            'quantidade'  => (int) $r->quantidade,
        ], DB::select($sql, ['dia' => $dia]));
    }

    public static function liquidadosLista(string $dia, int $codportador): array
    {
        $sql = "
            select
                tb.codtituloboleto,
                tb.codtitulo,
                tb.codportador,
                tb.vencimento,
                tb.datarecebimento,
                tb.datacredito,
                tb.valoratual,
                tb.valorjuromora,
                tb.valormulta,
                tb.valoroutro,
                tb.valorpago,
                tb.valorliquido,
                tb.nossonumero,
                tb.estadotitulocobranca,
                tb.tipobaixatitulo,
                abs(t.saldo) as saldo,
                t.numero,
                t.codpessoa,
                p.fantasia,
                po.portador
            from tbltituloboleto tb
            inner join tblportador po on (po.codportador = tb.codportador)
            inner join tbltitulo t on (t.codtitulo = tb.codtitulo)
            inner join tblpessoa p on (p.codpessoa = t.codpessoa)
            where tb.datacredito = :dia
              and tb.codportador = :codportador
            order by tb.valorpago desc
        ";
        return array_map(fn ($r) => (array) $r, DB::select($sql, [
            'dia'         => $dia,
            'codportador' => $codportador,
        ]));
    }

    public static function liquidadosNavegacao(
        ?string $ano = null,
        ?string $mes = null,
        ?string $dia = null,
        ?int $codportador = null
    ): array {
        $anos = self::liquidadosAnos();
        if (empty($ano) && !empty($anos)) {
            $ano = $anos[0]['ano'];
        }

        $meses = $ano ? self::liquidadosMeses($ano) : [];
        if (empty($mes) && !empty($meses)) {
            $mes = end($meses)['mes'];
        }

        $dias = ($ano && $mes) ? self::liquidadosDias($ano, $mes) : [];
        if (empty($dia) && !empty($dias)) {
            $dia = end($dias)['dia'];
        } elseif ($dia && strlen($dia) === 2 && $ano && $mes) {
            $dia = "{$ano}-{$mes}-{$dia}";
        }

        $portadores = $dia ? self::liquidadosPortadores($dia) : [];
        if (empty($codportador) && !empty($portadores)) {
            $codportador = $portadores[0]['codportador'];
        }

        $lista = ($dia && $codportador) ? self::liquidadosLista($dia, $codportador) : [];

        return [
            'ano'         => $ano,
            'mes'         => $mes,
            'dia'         => $dia,
            'codportador' => $codportador,
            'anos'        => $anos,
            'meses'       => $meses,
            'dias'        => $dias,
            'portadores'  => $portadores,
            'lista'       => $lista,
        ];
    }

    public static function baixadosLista(array $filtros = []): array
    {
        $where = ["tb.estadotitulocobranca = 7", "t.saldo != 0"];
        $params = [];

        if (!empty($filtros['codportador'])) {
            $where[] = "tb.codportador = :codportador";
            $params['codportador'] = (int) $filtros['codportador'];
        }
        if (!empty($filtros['tipobaixa'])) {
            $where[] = "tb.tipobaixatitulo = :tipobaixa";
            $params['tipobaixa'] = (int) $filtros['tipobaixa'];
        }

        $whereSql = implode(' and ', $where);
        $sql = "
            select
                tb.codtituloboleto,
                tb.codtitulo,
                tb.codportador,
                tb.vencimento,
                tb.databaixaautomatica,
                tb.valoratual,
                tb.nossonumero,
                tb.estadotitulocobranca,
                tb.tipobaixatitulo,
                abs(t.saldo) as saldo,
                t.numero,
                t.codpessoa,
                p.fantasia,
                po.portador
            from tbltituloboleto tb
            inner join tblportador po on (po.codportador = tb.codportador)
            inner join tbltitulo t on (t.codtitulo = tb.codtitulo)
            inner join tblpessoa p on (p.codpessoa = t.codpessoa)
            where {$whereSql}
            order by tb.vencimento desc, tb.valoratual desc
        ";
        return array_map(fn ($r) => (array) $r, DB::select($sql, $params));
    }
}
