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
    public const TIPO_PREMIO_META = 'PREMIO_META';
    public const TIPO_PREMIO_META_XEROX = 'PREMIO_META_XEROX';
    public const TIPO_PREMIO_META_SUBGERENTE = 'PREMIO_META_SUBGERENTE';

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
        $indicadoresXerox = static::calcularVendasXeroxPorUnidade($meta);
        $metasUnidade = MetaUnidadeNegocio::query()
            ->where('codmeta', $meta->codmeta)
            ->orderBy('codunidadenegocio')
            ->get();

        foreach ($metasUnidade as $metaUnidade) {
            $codunidade = intval($metaUnidade->codunidadenegocio);
            $totalVendasUnidade = floatval($indicadoresUnidade[$codunidade]['total_vendas'] ?? 0);
            $totalXeroxUnidade = floatval($indicadoresXerox[$codunidade] ?? 0);
            $valorMetaUnidade = floatval($metaUnidade->valormeta ?? 0);
            $valorMetaXerox = floatval($metaUnidade->valormetaxerox ?? 0);
            $valorMetaVendedor = floatval($metaUnidade->valormetavendedor ?? 0);

            $metaUnidadeAtingida = $valorMetaUnidade > 0 && $totalVendasUnidade >= $valorMetaUnidade;
            $metaXeroxAtingida = $valorMetaXerox > 0 && $totalXeroxUnidade >= $valorMetaXerox;

            Log::info('MetaService - Verificacao metas da unidade', [
                'codmeta' => $meta->codmeta,
                'codunidadenegocio' => $codunidade,
                'valormeta' => $valorMetaUnidade,
                'totalvendas' => static::arredondarValor($totalVendasUnidade),
                'metaatingida' => $metaUnidadeAtingida,
                'valormetaxerox' => $valorMetaXerox,
                'totalxerox' => static::arredondarValor($totalXeroxUnidade),
                'metaxeroxatingida' => $metaXeroxAtingida,
            ]);

            $rankingUnidade = static::calcularRankingVendasUnidade($meta, $codunidade);

            // META_ATINGIDA — vendedor bateu meta individual
            if (
                $metaUnidadeAtingida
                && $valorMetaVendedor > 0
                && !is_null($metaUnidade->percentualcomissaovendedormeta)
                && $metaUnidade->percentualcomissaovendedormeta > 0
            ) {
                foreach ($rankingUnidade as $rankingPessoa) {
                    $vendasPessoa = floatval($rankingPessoa->totalvendas);

                    if ($vendasPessoa < $valorMetaVendedor) {
                        continue;
                    }

                    $valorEvento = $vendasPessoa * floatval($metaUnidade->percentualcomissaovendedormeta) / 100;

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

            // PREMIO_RANKING — primeiro lugar da unidade
            if (!empty($metaUnidade->premioprimeirovendedor) && $metaUnidade->premioprimeirovendedor > 0 && $rankingUnidade->isNotEmpty()) {
                $primeiro = $rankingUnidade->first();

                static::adicionarEventoFinalEsperado(
                    $eventos,
                    static::TIPO_PREMIO_RANKING,
                    $codunidade,
                    intval($primeiro->codpessoa),
                    floatval($metaUnidade->premioprimeirovendedor),
                    'Premio ranking da unidade'
                );
            }

            // PREMIO_META_SUBGERENTE — se meta da loja atingida
            if ($metaUnidadeAtingida && !empty($metaUnidade->premiosubgerentemeta) && $metaUnidade->premiosubgerentemeta > 0) {
                $subgerentes = MetaUnidadeNegocioPessoa::query()
                    ->where('codmeta', $meta->codmeta)
                    ->where('codunidadenegocio', $codunidade)
                    ->whereNotNull('percentualsubgerente')
                    ->select('codpessoa')
                    ->distinct()
                    ->orderBy('codpessoa')
                    ->get();

                foreach ($subgerentes as $subgerente) {
                    static::adicionarEventoFinalEsperado(
                        $eventos,
                        static::TIPO_PREMIO_META_SUBGERENTE,
                        $codunidade,
                        intval($subgerente->codpessoa),
                        floatval($metaUnidade->premiosubgerentemeta),
                        'Premio meta subgerente'
                    );
                }
            }

            // PREMIO_META_XEROX — rateado entre xerox
            if ($metaXeroxAtingida && !empty($metaUnidade->premiometaxerox) && $metaUnidade->premiometaxerox > 0) {
                $pessoasXerox = MetaUnidadeNegocioPessoa::query()
                    ->where('codmeta', $meta->codmeta)
                    ->where('codunidadenegocio', $codunidade)
                    ->whereNotNull('percentualxerox')
                    ->where('percentualxerox', '>', 0)
                    ->select('codpessoa', DB::raw('max(percentualxerox) as percentualxerox'))
                    ->groupBy('codpessoa')
                    ->orderBy('codpessoa')
                    ->get();

                $somaPercentuais = $pessoasXerox->sum('percentualxerox');

                if ($somaPercentuais > 0) {
                    foreach ($pessoasXerox as $pessoaXerox) {
                        $valorRateado = floatval($metaUnidade->premiometaxerox)
                            * floatval($pessoaXerox->percentualxerox)
                            / $somaPercentuais;

                        static::adicionarEventoFinalEsperado(
                            $eventos,
                            static::TIPO_PREMIO_META_XEROX,
                            $codunidade,
                            intval($pessoaXerox->codpessoa),
                            $valorRateado,
                            'Premio meta xerox'
                        );
                    }
                }
            }

            // BONUS_FIXO e PREMIO_META — da tblmetaunidadenegociopessoafixo
            $fixos = MetaUnidadeNegocioPessoaFixo::query()
                ->where('codmeta', $meta->codmeta)
                ->where('codunidadenegocio', $codunidade)
                ->orderBy('codpessoa')
                ->get();

            foreach ($fixos as $fixo) {
                $tipoFixo = strtoupper($fixo->tipo);

                // PREMIO_META só paga se meta da unidade atingida
                if ($tipoFixo === 'PREMIO_META') {
                    if (!$metaUnidadeAtingida) {
                        continue;
                    }

                    static::adicionarEventoFinalEsperado(
                        $eventos,
                        static::TIPO_PREMIO_META,
                        $codunidade,
                        intval($fixo->codpessoa),
                        floatval($fixo->valor),
                        $fixo->descricao ?? 'Premio meta'
                    );
                    continue;
                }

                // ALIMENTACAO: valor × quantidade
                $valorFixo = floatval($fixo->valor);
                if ($tipoFixo === 'ALIMENTACAO' && !is_null($fixo->quantidade)) {
                    $valorFixo = $valorFixo * floatval($fixo->quantidade);
                }

                static::adicionarEventoFinalEsperado(
                    $eventos,
                    static::TIPO_BONUS_FIXO,
                    $codunidade,
                    intval($fixo->codpessoa),
                    $valorFixo,
                    $fixo->descricao ?? $fixo->tipo
                );
            }
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

            $retorno[$codunidade] = [
                'total_vendas' => floatval($registro->totalvendas),
            ];
        }

        return $retorno;
    }

    private static function calcularVendasXeroxPorUnidade(Meta $meta): array
    {
        $sql = <<<'SQL'
            select
                case
                    when pdv.alocacao = :alocacao_remota then
                        (select un.codunidadenegocio from tblunidadenegocio un where un.descricao = :descricao_unidade_remota and un.inativo is null limit 1)
                    else
                        (select un.codunidadenegocio from tblunidadenegocio un where un.codfilial = n.codfilial and un.inativo is null limit 1)
                end as codunidadenegocio,
                sum(case when nop.vendadevolucao = true then -abs(npb.valortotal) else npb.valortotal end) as totalxerox
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
              and coalesce(p.bonificacaoxerox, false) = true
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

            $retorno[$codunidade] = floatval($registro->totalxerox);
        }

        return $retorno;
    }

    private static function calcularRankingVendasUnidade(Meta $meta, int $codunidadenegocio)
    {
        $sql = <<<'SQL'
            select
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

    public static function duplicarConfiguracao(Meta $metaAnterior, Meta $novaMeta): void
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
                'percentualcomissaovendedor' => $configuracaoUnidade->percentualcomissaovendedor,
                'percentualcomissaovendedormeta' => $configuracaoUnidade->percentualcomissaovendedormeta,
                'percentualcomissaosubgerente' => $configuracaoUnidade->percentualcomissaosubgerente,
                'percentualcomissaosubgerentemeta' => $configuracaoUnidade->percentualcomissaosubgerentemeta,
                'percentualcomissaoxerox' => $configuracaoUnidade->percentualcomissaoxerox,
                'premioprimeirovendedor' => $configuracaoUnidade->premioprimeirovendedor,
                'premiosubgerentemeta' => $configuracaoUnidade->premiosubgerentemeta,
                'premiometaxerox' => $configuracaoUnidade->premiometaxerox,
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
                'datainicial' => $novaMeta->periodoinicial->toDateString(),
                'datafinal' => $novaMeta->periodofinal->toDateString(),
                'percentualvenda' => $configuracaoPessoa->percentualvenda,
                'percentualcaixa' => $configuracaoPessoa->percentualcaixa,
                'percentualsubgerente' => $configuracaoPessoa->percentualsubgerente,
                'percentualxerox' => $configuracaoPessoa->percentualxerox,
            ]);
        }

        $configuracoesFixo = MetaUnidadeNegocioPessoaFixo::query()
            ->where('codmeta', $metaAnterior->codmeta)
            ->orderBy('codunidadenegocio')
            ->orderBy('codpessoa')
            ->get();

        foreach ($configuracoesFixo as $configuracaoFixo) {
            MetaUnidadeNegocioPessoaFixo::create([
                'codmeta' => $novaMeta->codmeta,
                'codunidadenegocio' => $configuracaoFixo->codunidadenegocio,
                'codpessoa' => $configuracaoFixo->codpessoa,
                'tipo' => $configuracaoFixo->tipo,
                'valor' => $configuracaoFixo->valor,
                'quantidade' => $configuracaoFixo->quantidade,
                'descricao' => $configuracaoFixo->descricao,
                'datainicial' => $novaMeta->periodoinicial->toDateString(),
                'datafinal' => $novaMeta->periodofinal->toDateString(),
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
            static::TIPO_PREMIO_META,
            static::TIPO_PREMIO_META_XEROX,
            static::TIPO_PREMIO_META_SUBGERENTE,
        ];
    }

}
