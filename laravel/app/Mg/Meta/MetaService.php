<?php

namespace Mg\Meta;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MetaService
{
    public const TIPO_META_ATINGIDA = 'META_ATINGIDA';
    public const TIPO_PREMIO_RANKING = 'PREMIO_RANKING';
    public const TIPO_BONUS_FIXO = 'BONUS_FIXO';

    public const META_STATUS_ABERTA = 'A';
    public const META_STATUS_BLOQUEADA = 'B';
    public const META_STATUS_FECHADA = 'F';

    public const ALOCACAO_REMOTA = 'R';
    public const DESCRICAO_UNIDADE_REMOTA = 'Sinopel';

    public static function apurarMovimentosFinais(Meta $meta): void
    {
        if ($meta->status !== static::META_STATUS_BLOQUEADA) {
            throw new RuntimeException("Meta {$meta->codmeta} precisa estar bloqueada para apurar movimentos finais.");
        }

        Log::info('MetaService - Fechamento iniciado', [
            'codmeta' => $meta->codmeta,
            'status' => $meta->status,
        ]);

        $eventosEsperados = static::calcularEventosFinaisEsperados($meta);

        static::executarMergeEventosFinais($meta, $eventosEsperados);

        Log::info('MetaService - Fechamento concluido', [
            'codmeta' => $meta->codmeta,
            'totaleventosesperados' => count($eventosEsperados),
        ]);
    }

    public static function criarNovaMeta(Meta $metaAnterior): Meta
    {
        $metaAberta = Meta::query()
            ->where('status', static::META_STATUS_ABERTA)
            ->first();

        if (!empty($metaAberta)) {
            throw new RuntimeException("Ja existe meta aberta ({$metaAberta->codmeta}).");
        }

        $novoPeriodoInicial = $metaAnterior->periodofinal->copy()->addDay()->startOfDay();
        $novoPeriodoFinal = $novoPeriodoInicial->copy()->endOfMonth()->endOfDay();

        $novaMeta = Meta::create([
            'periodoinicial' => $novoPeriodoInicial,
            'periodofinal' => $novoPeriodoFinal,
            'status' => static::META_STATUS_ABERTA,
            'processando' => false,
            'percentualcomissaovendedor' => $metaAnterior->percentualcomissaovendedor,
            'percentualcomissaovendedormeta' => $metaAnterior->percentualcomissaovendedormeta,
            'percentualcomissaoxerox' => $metaAnterior->percentualcomissaoxerox,
            'percentualcomissaosubgerentemeta' => $metaAnterior->percentualcomissaosubgerentemeta,
            'premioprimeirovendedorfilial' => $metaAnterior->premioprimeirovendedorfilial,
            'observacoes' => $metaAnterior->observacoes,
        ]);

        static::duplicarConfiguracao($metaAnterior, $novaMeta);

        Log::info('MetaService - Meta criada', [
            'codmetaanterior' => $metaAnterior->codmeta,
            'codmetanova' => $novaMeta->codmeta,
            'periodoinicial' => $novoPeriodoInicial->toDateString(),
            'periodofinal' => $novoPeriodoFinal->toDateString(),
        ]);

        return $novaMeta;
    }

    private static function calcularEventosFinaisEsperados(Meta $meta): array
    {
        $eventos = [];

        $indicadoresUnidade = static::calcularIndicadoresPorUnidade($meta);
        $metasUnidade = MetaUnidadeNegocio::query()
            ->where('codmeta', $meta->codmeta)
            ->orderBy('codunidadenegocio')
            ->get();

        foreach ($metasUnidade as $metaUnidade) {
            $codunidade = intval($metaUnidade->codunidadenegocio);
            $totalVendasUnidade = floatval($indicadoresUnidade[$codunidade]['total_vendas'] ?? 0);
            $valorMetaUnidade = floatval($metaUnidade->valormeta ?? 0);

            $percentualAtingimento = 0;
            if ($valorMetaUnidade > 0) {
                $percentualAtingimento = ($totalVendasUnidade / $valorMetaUnidade) * 100;
            }

            Log::info('MetaService - Verificacao metas da unidade', [
                'codmeta' => $meta->codmeta,
                'codunidadenegocio' => $codunidade,
                'valormeta' => $valorMetaUnidade,
                'totalvendas' => static::arredondarValor($totalVendasUnidade),
                'percentualatingimento' => static::arredondarValor($percentualAtingimento),
            ]);

            $rankingUnidade = static::calcularRankingVendasUnidade($meta, $codunidade);

            if (
                $valorMetaUnidade > 0
                && $totalVendasUnidade >= $valorMetaUnidade
                && !is_null($meta->percentualcomissaovendedormeta)
                && $meta->percentualcomissaovendedormeta > 0
            ) {
                foreach ($rankingUnidade as $rankingPessoa) {
                    $valorEvento = floatval($rankingPessoa->totalvendas) * floatval($meta->percentualcomissaovendedormeta) / 100;

                    static::adicionarEventoFinalEsperado(
                        $eventos,
                        static::TIPO_META_ATINGIDA,
                        $codunidade,
                        intval($rankingPessoa->codpessoa),
                        $valorEvento,
                        'Meta atingida da unidade'
                    );
                }
            }

            if (!empty($meta->premioprimeirovendedorfilial) && $meta->premioprimeirovendedorfilial > 0 && $rankingUnidade->isNotEmpty()) {
                $primeiro = $rankingUnidade->first();

                static::adicionarEventoFinalEsperado(
                    $eventos,
                    static::TIPO_PREMIO_RANKING,
                    $codunidade,
                    intval($primeiro->codpessoa),
                    floatval($meta->premioprimeirovendedorfilial),
                    'Premio ranking da unidade'
                );
            }
        }

        $bonusFixos = MetaUnidadeNegocioPessoa::query()
            ->where('codmeta', $meta->codmeta)
            ->whereNotNull('valorfixo')
            ->where('valorfixo', '>', 0)
            ->orderBy('codunidadenegocio')
            ->orderBy('codpessoa')
            ->get();

        foreach ($bonusFixos as $bonusFixo) {
            static::adicionarEventoFinalEsperado(
                $eventos,
                static::TIPO_BONUS_FIXO,
                intval($bonusFixo->codunidadenegocio),
                intval($bonusFixo->codpessoa),
                floatval($bonusFixo->valorfixo),
                $bonusFixo->descricaovalorfixo ?? 'Bonus fixo'
            );
        }

        ksort($eventos);

        return array_values($eventos);
    }

    private static function adicionarEventoFinalEsperado(
        array &$eventos,
        string $tipo,
        int $codunidadenegocio,
        int $codpessoa,
        float $valor,
        string $descricao
    ): void {
        $chave = static::montarChaveEventoFinal($tipo, $codunidadenegocio, $codpessoa);

        if (!isset($eventos[$chave])) {
            $eventos[$chave] = [
                'tipo' => $tipo,
                'codunidadenegocio' => $codunidadenegocio,
                'codpessoa' => $codpessoa,
                'descricao' => $descricao,
                'valor' => static::arredondarValor($valor),
            ];
            return;
        }

        $eventos[$chave]['valor'] = static::arredondarValor($eventos[$chave]['valor'] + $valor);
    }

    private static function executarMergeEventosFinais(Meta $meta, array $eventosEsperados): void
    {
        $eventosExistentes = BonificacaoEvento::query()
            ->where('codmeta', $meta->codmeta)
            ->where('manual', false)
            ->whereNull('codnegocio')
            ->whereIn('tipo', static::tiposFinais())
            ->orderBy('codbonificacaoevento')
            ->get();

        $mapaExistentes = [];

        foreach ($eventosExistentes as $eventoExistente) {
            $chave = static::montarChaveEventoFinal(
                $eventoExistente->tipo,
                intval($eventoExistente->codunidadenegocio),
                intval($eventoExistente->codpessoa)
            );

            if (isset($mapaExistentes[$chave])) {
                $eventoExistente->delete();

                Log::info('MetaService - Evento removido', [
                    'codbonificacaoevento' => $eventoExistente->codbonificacaoevento,
                    'codmeta' => $meta->codmeta,
                    'tipo' => $eventoExistente->tipo,
                    'codpessoa' => $eventoExistente->codpessoa,
                    'valor' => $eventoExistente->valor,
                ]);

                continue;
            }

            $mapaExistentes[$chave] = $eventoExistente;
        }

        $chavesEsperadas = [];

        foreach ($eventosEsperados as $eventoEsperado) {
            $chave = static::montarChaveEventoFinal(
                $eventoEsperado['tipo'],
                $eventoEsperado['codunidadenegocio'],
                $eventoEsperado['codpessoa']
            );
            $chavesEsperadas[] = $chave;

            if (isset($mapaExistentes[$chave])) {
                $eventoExistente = $mapaExistentes[$chave];

                if (!static::eventoFinalIgual($eventoExistente, $eventoEsperado)) {
                    $eventoExistente->update([
                        'descricao' => $eventoEsperado['descricao'],
                        'valor' => $eventoEsperado['valor'],
                    ]);

                    Log::info('MetaService - Evento atualizado', [
                        'codbonificacaoevento' => $eventoExistente->codbonificacaoevento,
                        'codmeta' => $meta->codmeta,
                        'tipo' => $eventoExistente->tipo,
                        'codpessoa' => $eventoExistente->codpessoa,
                        'valor' => $eventoEsperado['valor'],
                    ]);
                }

                continue;
            }

            $evento = BonificacaoEvento::create([
                'codmeta' => $meta->codmeta,
                'codnegocio' => null,
                'codunidadenegocio' => $eventoEsperado['codunidadenegocio'],
                'codpessoa' => $eventoEsperado['codpessoa'],
                'tipo' => $eventoEsperado['tipo'],
                'descricao' => $eventoEsperado['descricao'],
                'valor' => $eventoEsperado['valor'],
                'manual' => false,
            ]);

            Log::info('MetaService - Evento criado', [
                'codbonificacaoevento' => $evento->codbonificacaoevento,
                'codmeta' => $meta->codmeta,
                'tipo' => $evento->tipo,
                'codpessoa' => $evento->codpessoa,
                'valor' => $evento->valor,
            ]);
        }

        foreach ($mapaExistentes as $chave => $eventoExistente) {
            if (in_array($chave, $chavesEsperadas)) {
                continue;
            }

            $eventoExistente->delete();

            Log::info('MetaService - Evento removido', [
                'codbonificacaoevento' => $eventoExistente->codbonificacaoevento,
                'codmeta' => $meta->codmeta,
                'tipo' => $eventoExistente->tipo,
                'codpessoa' => $eventoExistente->codpessoa,
                'valor' => $eventoExistente->valor,
            ]);
        }
    }

    private static function eventoFinalIgual(BonificacaoEvento $eventoExistente, array $eventoEsperado): bool
    {
        return (
            intval($eventoExistente->codunidadenegocio) === intval($eventoEsperado['codunidadenegocio'])
            && intval($eventoExistente->codpessoa) === intval($eventoEsperado['codpessoa'])
            && $eventoExistente->tipo === $eventoEsperado['tipo']
            && $eventoExistente->descricao === $eventoEsperado['descricao']
            && abs(static::arredondarValor($eventoExistente->valor - $eventoEsperado['valor'])) < 0.01
        );
    }

    private static function calcularIndicadoresPorUnidade(Meta $meta): array
    {
        $sql = <<<'SQL'
            select
                case
                    when pdv.alocacao = :alocacao_remota then
                        (select un.codunidadenegocio from tblunidadenegocio un where un.descricao = :descricao_unidade_remota and un.inativo is null limit 1)
                    else
                        (select un.codunidadenegocio from tblunidadenegocio un where un.codfilial = n.codfilial and un.inativo is null limit 1)
                end as codunidadenegocio,
                sum(npb.valortotal) as totalvendas
            from tblnegocio n
            inner join tblnegocioprodutobarra npb on npb.codnegocio = n.codnegocio
            left join tblpdv pdv on pdv.codpdv = n.codpdv
            where n.lancamento between :periodoinicial and :periodofinal
              and n.codnegociostatus = 2
              and npb.inativo is null
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

            $retorno[$codunidade] = [
                'total_vendas' => floatval($registro->totalvendas),
            ];
        }

        return $retorno;
    }

    private static function calcularRankingVendasUnidade(Meta $meta, int $codunidadenegocio)
    {
        $sql = <<<'SQL'
            select
                n.codpessoavendedor as codpessoa,
                sum(npb.valortotal) as totalvendas
            from tblnegocio n
            inner join tblnegocioprodutobarra npb on npb.codnegocio = n.codnegocio
            left join tblpdv pdv on pdv.codpdv = n.codpdv
            where n.lancamento between :periodoinicial and :periodofinal
              and n.codnegociostatus = 2
              and npb.inativo is null
              and n.codpessoavendedor is not null
              and case
                    when pdv.alocacao = :alocacao_remota then
                        (select un.codunidadenegocio from tblunidadenegocio un where un.descricao = :descricao_unidade_remota and un.inativo is null limit 1)
                    else
                        (select un.codunidadenegocio from tblunidadenegocio un where un.codfilial = n.codfilial and un.inativo is null limit 1)
                  end = :codunidadenegocio
            group by n.codpessoavendedor
            order by totalvendas desc, n.codpessoavendedor asc
        SQL;

        return collect(DB::select($sql, [
            'alocacao_remota' => static::ALOCACAO_REMOTA,
            'descricao_unidade_remota' => static::DESCRICAO_UNIDADE_REMOTA,
            'periodoinicial' => $meta->periodoinicial->copy()->startOfDay(),
            'periodofinal' => $meta->periodofinal->copy()->endOfDay(),
            'codunidadenegocio' => $codunidadenegocio,
        ]));
    }

    private static function duplicarConfiguracao(Meta $metaAnterior, Meta $novaMeta): void
    {
        $configuracoesUnidade = MetaUnidadeNegocio::query()
            ->where('codmeta', $metaAnterior->codmeta)
            ->orderBy('codunidadenegocio')
            ->get();

        foreach ($configuracoesUnidade as $configuracaoUnidade) {
            MetaUnidadeNegocio::create([
                'codmeta' => $novaMeta->codmeta,
                'codunidadenegocio' => $configuracaoUnidade->codunidadenegocio,
                'valormeta' => $configuracaoUnidade->valormeta,
                'valormetacaixa' => $configuracaoUnidade->valormetacaixa,
                'valormetavendedor' => $configuracaoUnidade->valormetavendedor,
                'valormetaxerox' => $configuracaoUnidade->valormetaxerox,
            ]);
        }

        $configuracoesPessoa = MetaUnidadeNegocioPessoa::query()
            ->where('codmeta', $metaAnterior->codmeta)
            ->orderBy('codunidadenegocio')
            ->orderBy('codpessoa')
            ->get();

        foreach ($configuracoesPessoa as $configuracaoPessoa) {
            MetaUnidadeNegocioPessoa::create([
                'codmeta' => $novaMeta->codmeta,
                'codunidadenegocio' => $configuracaoPessoa->codunidadenegocio,
                'codpessoa' => $configuracaoPessoa->codpessoa,
                'percentualvenda' => $configuracaoPessoa->percentualvenda,
                'percentualcaixa' => $configuracaoPessoa->percentualcaixa,
                'percentualsubgerente' => $configuracaoPessoa->percentualsubgerente,
                'percentualxerox' => $configuracaoPessoa->percentualxerox,
                'valorfixo' => $configuracaoPessoa->valorfixo,
                'descricaovalorfixo' => $configuracaoPessoa->descricaovalorfixo,
            ]);
        }
    }

    private static function montarChaveEventoFinal(string $tipo, int $codunidadenegocio, int $codpessoa): string
    {
        return implode('|', [$tipo, $codunidadenegocio, $codpessoa]);
    }

    private static function arredondarValor(float $valor): float
    {
        return round($valor, 2);
    }

    private static function tiposFinais(): array
    {
        return [
            static::TIPO_META_ATINGIDA,
            static::TIPO_PREMIO_RANKING,
            static::TIPO_BONUS_FIXO,
        ];
    }

    public static function refreshViews()
    {
        // metodo mantido por compatibilidade
    }

    public static function vendasFilial(Meta $meta)
    {
        $sql = '
            select v.codfilial, v.filial, v.dia, sum(valorvenda) as valorvenda
            from mwvendas v
            where v.dia between :inicial and :final
            and v.comissaovendedor = 1
            group by v.codfilial, v.filial, v.dia
            order by v.codfilial, v.filial, v.dia
        ';

        $regs = DB::select($sql, [
            'inicial' => $meta->periodoinicial,
            'final' => $meta->periodofinal,
        ]);

        $filiais = collect($regs)->groupBy('codfilial');
        $ret = [];

        foreach ($filiais as $codfilial => $dias) {
            $metaFilial = MetaFilial::firstOrNew([
                'codmeta' => $meta->codmeta,
                'codfilial' => $codfilial,
            ])->toArray();

            $metaFilial['filial'] = $dias[0]->filial;
            $metaFilial['dias'] = $dias->transform(function ($dia) {
                return [
                    'dia' => $dia->dia,
                    'valorvenda' => floatval($dia->valorvenda),
                ];
            });
            $metaFilial['valorvenda'] = $dias->sum('valorvenda');

            if (($metaFilial['valormetafilial'] ?? 0) > 0) {
                $metaFilial['progresso'] = $dias->sum('valorvenda') / $metaFilial['valormetafilial'];
                $metaFilial['estrelas'] = round($metaFilial['progresso'] * 5, 1);
            } else {
                $metaFilial['progresso'] = null;
                $metaFilial['estrelas'] = null;
            }

            $metaFilial['valorcomissao'] = $metaFilial['valorvenda'] * $meta->percentualcomissaosubgerentemeta * 0.01;
            $ret[] = $metaFilial;
        }

        return $ret;
    }

    public static function vendasVendedor(Meta $meta)
    {
        $sql = '
            select v.codpessoavendedor, v.vendedor, v.dia, sum(valorvenda) as valorvenda
            from mwvendas v
            where v.dia between :inicial and :final
            and v.comissaovendedor = 2
            group by v.codpessoavendedor, v.vendedor, v.dia
            order by v.codpessoavendedor, v.vendedor, v.dia
        ';

        $regs = DB::select($sql, [
            'inicial' => $meta->periodoinicial,
            'final' => $meta->periodofinal,
        ]);

        $vendedores = collect($regs)->groupBy('codpessoavendedor');
        $ret = [];

        foreach ($vendedores as $codpessoavendedor => $dias) {
            $metaVendedor = MetaVendedor::firstOrNew([
                'codmeta' => $meta->codmeta,
                'codpessoa' => $codpessoavendedor,
            ])->toArray();

            $metaVendedor['dias'] = $dias->transform(function ($dia) {
                return [
                    'dia' => $dia->dia,
                    'valorvenda' => floatval($dia->valorvenda),
                ];
            });

            $metaVendedor['valorvenda'] = $dias->sum('valorvenda');
            $ret[] = $metaVendedor;
        }

        return $ret;
    }
}
