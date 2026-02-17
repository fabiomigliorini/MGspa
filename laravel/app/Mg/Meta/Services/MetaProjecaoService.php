<?php

namespace Mg\Meta\Services;

use Illuminate\Support\Facades\DB;
use Mg\Meta\Meta;
use Mg\Meta\MetaUnidadeNegocio;

class MetaProjecaoService
{
    public const ALOCACAO_REMOTA = 'R';
    public const DESCRICAO_UNIDADE_REMOTA = 'Sinopel';

    public static function projecao(Meta $meta): array
    {
        $totaisUnidade = static::calcularTotaisPorUnidade($meta);
        $totaisPessoa = static::calcularTotaisPorPessoa($meta);

        // Buscar nomes e cargos de todas as pessoas envolvidas
        $codpessoas = array_values(array_unique(array_column($totaisPessoa, 'codpessoa')));
        $dadosPessoas = static::buscarDadosPessoas($codpessoas);

        $metasUnidade = MetaUnidadeNegocio::query()
            ->where('codmeta', $meta->codmeta)
            ->with('UnidadeNegocio')
            ->orderBy('codunidadenegocio')
            ->get();

        $unidades = [];
        $pessoasAgrupadas = [];

        foreach ($metasUnidade as $metaUnidade) {
            $codunidade = intval($metaUnidade->codunidadenegocio);
            $valorMeta = floatval($metaUnidade->valormeta ?? 0);
            $totalUnidade = floatval($totaisUnidade[$codunidade] ?? 0);

            $percentualAtingimento = null;
            if ($valorMeta > 0) {
                $percentualAtingimento = round(($totalUnidade / $valorMeta) * 100, 2);
            }

            $rankingUnidade = collect($totaisPessoa)
                ->where('codunidadenegocio', $codunidade)
                ->sortBy([
                    ['totalvendas', 'desc'],
                    ['codpessoa', 'asc'],
                ])
                ->values();

            $ranking = [];
            $posicao = 1;

            foreach ($rankingUnidade as $registroPessoa) {
                $codpessoa = intval($registroPessoa['codpessoa']);
                $totalPessoa = floatval($registroPessoa['totalvendas']);

                $ranking[] = [
                    'posicao' => $posicao,
                    'codpessoa' => $codpessoa,
                    'pessoa' => $dadosPessoas[$codpessoa]['pessoa'] ?? null,
                    'cargo' => $dadosPessoas[$codpessoa]['cargo'] ?? null,
                    'totalvendas' => $totalPessoa,
                ];

                if (!isset($pessoasAgrupadas[$codpessoa])) {
                    $pessoasAgrupadas[$codpessoa] = [
                        'codpessoa' => $codpessoa,
                        'pessoa' => $dadosPessoas[$codpessoa]['pessoa'] ?? null,
                        'cargo' => $dadosPessoas[$codpessoa]['cargo'] ?? null,
                        'totalvendas' => 0.0,
                    ];
                }

                $pessoasAgrupadas[$codpessoa]['totalvendas'] += $totalPessoa;
                $pessoasAgrupadas[$codpessoa]['totalvendas'] = round($pessoasAgrupadas[$codpessoa]['totalvendas'], 2);

                $posicao++;
            }

            $unidades[] = [
                'codunidadenegocio' => $codunidade,
                'descricao' => $metaUnidade->UnidadeNegocio->descricao ?? null,
                'valormeta' => $valorMeta,
                'totalvendas' => round($totalUnidade, 2),
                'percentualatingimento' => $percentualAtingimento,
                'metaatingida' => ($valorMeta > 0 && $totalUnidade >= $valorMeta),
                'rankingprovisorio' => $ranking,
            ];
        }

        $pessoas = array_values($pessoasAgrupadas);
        usort($pessoas, function ($a, $b) {
            if ($a['totalvendas'] == $b['totalvendas']) {
                return $a['codpessoa'] <=> $b['codpessoa'];
            }
            return $a['totalvendas'] < $b['totalvendas'] ? 1 : -1;
        });

        return [
            'codmeta' => $meta->codmeta,
            'unidades' => $unidades,
            'pessoas' => $pessoas,
        ];
    }

    public static function resumoPessoa(Meta $meta, int $codpessoa): array
    {
        $pessoa = DB::selectOne('
            select
                p.fantasia as pessoa,
                c.cargo
            from tblpessoa p
            left join tblcolaborador col on col.codpessoa = p.codpessoa
            left join tblcolaboradorcargo cc on cc.codcolaborador = col.codcolaborador and cc.fim is null
            left join tblcargo c on c.codcargo = cc.codcargo
            where p.codpessoa = :codpessoa
            limit 1
        ', [
            'codpessoa' => $codpessoa,
        ]);

        $eventos = DB::select('
            select tipo, sum(valor) as total
            from tblbonificacaoevento
            where codmeta = :codmeta
            and codpessoa = :codpessoa
            group by tipo
        ', [
            'codmeta' => $meta->codmeta,
            'codpessoa' => $codpessoa,
        ]);

        $totais = [];
        $totalGeral = 0;

        foreach ($eventos as $evento) {
            $totais[$evento->tipo] = floatval($evento->total);
            $totalGeral += floatval($evento->total);
        }

        return [
            'codpessoa' => $codpessoa,
            'pessoa' => $pessoa->pessoa ?? null,
            'cargo' => $pessoa->cargo ?? null,
            'codmeta' => $meta->codmeta,
            'eventos' => $totais,
            'totalGeral' => round($totalGeral, 2),
        ];
    }

    private static function buscarDadosPessoas(array $codpessoas): array
    {
        if (empty($codpessoas)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($codpessoas), '?'));

        $registros = DB::select("
            select
                p.codpessoa,
                p.fantasia as pessoa,
                c.cargo
            from tblpessoa p
            left join tblcolaborador col on col.codpessoa = p.codpessoa
            left join tblcolaboradorcargo cc on cc.codcolaborador = col.codcolaborador and cc.fim is null
            left join tblcargo c on c.codcargo = cc.codcargo
            where p.codpessoa in ({$placeholders})
        ", $codpessoas);

        $mapa = [];
        foreach ($registros as $reg) {
            $cod = intval($reg->codpessoa);
            if (!isset($mapa[$cod])) {
                $mapa[$cod] = [
                    'pessoa' => $reg->pessoa,
                    'cargo' => $reg->cargo,
                ];
            }
        }

        return $mapa;
    }

    private static function calcularTotaisPorUnidade(Meta $meta): array
    {
        $sql = <<<'SQL'
            select
                case
                    when pdv.alocacao = :alocacao_remota then
                        (select un.codunidadenegocio from tblunidadenegocio un where un.descricao = :descricao_unidade_remota and un.inativo is null limit 1)
                    else
                        (select un.codunidadenegocio from tblunidadenegocio un where un.codfilial = n.codfilial and un.inativo is null limit 1)
                end as codunidadenegocio,
                sum(case when nop.vendadevolucao = true then -abs(npb.valortotal) else npb.valortotal end) as totalvendas
            from tblnegocio n
            inner join tblnaturezaoperacao nop on nop.codnaturezaoperacao = n.codnaturezaoperacao
            inner join tblnegocioprodutobarra npb on npb.codnegocio = n.codnegocio
            left join tblpdv pdv on pdv.codpdv = n.codpdv
            where n.lancamento between :periodoinicial and :periodofinal
              and n.codnegociostatus = 2
              and npb.inativo is null
              and (nop.venda = true or nop.vendadevolucao = true)
            group by 1
        SQL;

        $registros = DB::select($sql, [
            'alocacao_remota' => static::ALOCACAO_REMOTA,
            'descricao_unidade_remota' => static::DESCRICAO_UNIDADE_REMOTA,
            'periodoinicial' => $meta->periodoinicial->copy()->startOfDay(),
            'periodofinal' => $meta->periodofinal->copy()->endOfDay(),
        ]);

        $retorno = [];

        foreach ($registros as $registro) {
            $codunidade = intval($registro->codunidadenegocio);

            if (empty($codunidade)) {
                continue;
            }

            $retorno[$codunidade] = round(floatval($registro->totalvendas), 2);
        }

        return $retorno;
    }

    private static function calcularTotaisPorPessoa(Meta $meta): array
    {
        $sql = <<<'SQL'
            select
                case
                    when pdv.alocacao = :alocacao_remota then
                        (select un.codunidadenegocio from tblunidadenegocio un where un.descricao = :descricao_unidade_remota and un.inativo is null limit 1)
                    else
                        (select un.codunidadenegocio from tblunidadenegocio un where un.codfilial = n.codfilial and un.inativo is null limit 1)
                end as codunidadenegocio,
                n.codpessoavendedor as codpessoa,
                sum(case when nop.vendadevolucao = true then -abs(npb.valortotal) else npb.valortotal end) as totalvendas
            from tblnegocio n
            inner join tblnaturezaoperacao nop on nop.codnaturezaoperacao = n.codnaturezaoperacao
            inner join tblnegocioprodutobarra npb on npb.codnegocio = n.codnegocio
            inner join tblprodutobarra pb on pb.codprodutobarra = npb.codprodutobarra
            inner join tblproduto p on p.codproduto = pb.codproduto
            left join tblpdv pdv on pdv.codpdv = n.codpdv
            where n.lancamento between :periodoinicial and :periodofinal
              and n.codnegociostatus = 2
              and npb.inativo is null
              and (nop.venda = true or nop.vendadevolucao = true)
              and n.codpessoavendedor is not null
              and coalesce(p.bonificacaoxerox, false) = false
            group by 1, 2
            order by 1, totalvendas desc, 2
        SQL;

        $registros = DB::select($sql, [
            'alocacao_remota' => static::ALOCACAO_REMOTA,
            'descricao_unidade_remota' => static::DESCRICAO_UNIDADE_REMOTA,
            'periodoinicial' => $meta->periodoinicial->copy()->startOfDay(),
            'periodofinal' => $meta->periodofinal->copy()->endOfDay(),
        ]);

        $retorno = [];

        foreach ($registros as $registro) {
            $codunidade = intval($registro->codunidadenegocio);
            $codpessoa = intval($registro->codpessoa);

            if (empty($codunidade) || empty($codpessoa)) {
                continue;
            }

            $retorno[] = [
                'codunidadenegocio' => $codunidade,
                'codpessoa' => $codpessoa,
                'totalvendas' => round(floatval($registro->totalvendas), 2),
            ];
        }

        return $retorno;
    }
}
