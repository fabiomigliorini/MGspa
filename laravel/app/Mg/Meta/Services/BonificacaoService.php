<?php

namespace Mg\Meta\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Colaborador\Colaborador;
use Mg\Filial\UnidadeNegocio;
use Mg\Meta\BonificacaoEvento;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;
use Mg\Meta\MetaUnidadeNegocioPessoa;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;

class BonificacaoService
{
    public const TIPO_VENDA_VENDEDOR = 'VENDA_VENDEDOR';
    public const TIPO_VENDA_CAIXA = 'VENDA_CAIXA';
    public const TIPO_VENDA_LOJA = 'VENDA_LOJA';
    public const TIPO_VENDA_XEROX = 'VENDA_XEROX';

    public const STATUS_NEGOCIO_ABERTO = 1;
    public const STATUS_NEGOCIO_FECHADO = 2;
    public const STATUS_NEGOCIO_CANCELADO = 3;

    public const ALOCACAO_CAIXA = 'C';
    public const ALOCACAO_REMOTA = MetaService::ALOCACAO_REMOTA;

    public const DESCRICAO_UNIDADE_REMOTA = MetaService::DESCRICAO_UNIDADE_REMOTA;

    public static function processarNegocio(int $codnegocio, Meta $meta): void
    {
        $negocio = Negocio::with(['Pdv', 'NaturezaOperacao'])->findOrFail($codnegocio);

        $natureza = $negocio->NaturezaOperacao;
        $ehVenda = $natureza->venda ?? false;
        $ehDevolucao = $natureza->vendadevolucao ?? false;

        if (!$ehVenda && !$ehDevolucao) {
            return;
        }

        Log::info('BonificacaoService - Inicio de processamento', [
            'codmeta' => $meta->codmeta,
            'codnegocio' => $negocio->codnegocio,
            'codnegociostatus' => $negocio->codnegociostatus,
            'devolucao' => $ehDevolucao,
        ]);

        if ($negocio->codnegociostatus == static::STATUS_NEGOCIO_ABERTO) {
            return;
        }

        if ($negocio->codnegociostatus == static::STATUS_NEGOCIO_CANCELADO) {
            static::processarCancelamento($meta, $negocio);
            return;
        }

        if ($negocio->codnegociostatus != static::STATUS_NEGOCIO_FECHADO) {
            return;
        }

        $unidade = static::identificarUnidadeNegocio($negocio);
        $bases = static::calcularBasesNegocio($negocio->codnegocio, $ehDevolucao);
        $eventosEsperados = static::calcularEventosEsperados($meta, $negocio, $unidade, $bases);

        static::executarMergeEventosVenda($meta, $negocio, $unidade, $eventosEsperados);
    }

    private static function processarCancelamento(Meta $meta, Negocio $negocio): void
    {
        $eventosPositivos = BonificacaoEvento::query()
            ->where('codmeta', $meta->codmeta)
            ->where('codnegocio', $negocio->codnegocio)
            ->where('manual', false)
            ->whereIn('tipo', static::tiposVenda())
            ->where('valor', '>', 0)
            ->orderBy('codbonificacaoevento')
            ->get();

        if ($eventosPositivos->isEmpty()) {
            return;
        }

        Log::info('BonificacaoService - Estorno detectado', [
            'codmeta' => $meta->codmeta,
            'codnegocio' => $negocio->codnegocio,
            'totaleventospositivos' => $eventosPositivos->count(),
        ]);

        foreach ($eventosPositivos as $eventoPositivo) {
            if (static::existeEstornoEspelhado($meta, $negocio, $eventoPositivo)) {
                continue;
            }

            $evento = BonificacaoEvento::create([
                'codmeta' => $meta->codmeta,
                'codnegocio' => $negocio->codnegocio,
                'codunidadenegocio' => $eventoPositivo->codunidadenegocio,
                'codpessoa' => $eventoPositivo->codpessoa,
                'tipo' => $eventoPositivo->tipo,
                'valor' => static::arredondarValor(-$eventoPositivo->valor),
                'lancamento' => $negocio->lancamento,
                'manual' => false,
            ]);

            Log::info('BonificacaoService - Evento criado', [
                'codbonificacaoevento' => $evento->codbonificacaoevento,
                'codmeta' => $meta->codmeta,
                'codnegocio' => $negocio->codnegocio,
                'tipo' => $evento->tipo,
                'codpessoa' => $evento->codpessoa,
                'valor' => $evento->valor,
            ]);
        }
    }

    private static function existeEstornoEspelhado(Meta $meta, Negocio $negocio, BonificacaoEvento $eventoPositivo): bool
    {
        $eventosNegativos = BonificacaoEvento::query()
            ->where('codmeta', $meta->codmeta)
            ->where('codnegocio', $negocio->codnegocio)
            ->where('manual', false)
            ->where('tipo', $eventoPositivo->tipo)
            ->where('codpessoa', $eventoPositivo->codpessoa)
            ->where('codunidadenegocio', $eventoPositivo->codunidadenegocio)
            ->where('valor', '<', 0)
            ->get();

        foreach ($eventosNegativos as $eventoNegativo) {
            if (abs(static::arredondarValor($eventoNegativo->valor + $eventoPositivo->valor)) < 0.00001) {
                return true;
            }
        }

        return false;
    }

    private static function executarMergeEventosVenda(Meta $meta, Negocio $negocio, UnidadeNegocio $unidade, array $eventosEsperados): void
    {
        $eventosExistentes = BonificacaoEvento::query()
            ->where('codmeta', $meta->codmeta)
            ->where('codnegocio', $negocio->codnegocio)
            ->where('manual', false)
            ->whereIn('tipo', static::tiposVenda())
            ->orderBy('codbonificacaoevento')
            ->get();

        $mapaExistentes = [];

        foreach ($eventosExistentes as $eventoExistente) {
            $chave = static::montarChaveEvento($eventoExistente->tipo, $eventoExistente->codpessoa);

            if (isset($mapaExistentes[$chave])) {
                $eventoExistente->delete();

                Log::info('BonificacaoService - Evento removido', [
                    'codbonificacaoevento' => $eventoExistente->codbonificacaoevento,
                    'codmeta' => $meta->codmeta,
                    'codnegocio' => $negocio->codnegocio,
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
            $chave = static::montarChaveEvento($eventoEsperado['tipo'], $eventoEsperado['codpessoa']);
            $chavesEsperadas[] = $chave;

            if (isset($mapaExistentes[$chave])) {
                $eventoExistente = $mapaExistentes[$chave];

                if (!static::eventoIgualAoEsperado($eventoExistente, $eventoEsperado, $unidade->codunidadenegocio)) {
                    $eventoExistente->update([
                        'codunidadenegocio' => $unidade->codunidadenegocio,
                        'valor' => $eventoEsperado['valor'],
                        'lancamento' => $eventoEsperado['lancamento'],
                    ]);

                    Log::info('BonificacaoService - Evento atualizado', [
                        'codbonificacaoevento' => $eventoExistente->codbonificacaoevento,
                        'codmeta' => $meta->codmeta,
                        'codnegocio' => $negocio->codnegocio,
                        'tipo' => $eventoExistente->tipo,
                        'codpessoa' => $eventoExistente->codpessoa,
                        'valor' => $eventoEsperado['valor'],
                    ]);
                }

                continue;
            }

            $evento = BonificacaoEvento::create([
                'codmeta' => $meta->codmeta,
                'codnegocio' => $negocio->codnegocio,
                'codunidadenegocio' => $unidade->codunidadenegocio,
                'codpessoa' => $eventoEsperado['codpessoa'],
                'tipo' => $eventoEsperado['tipo'],
                'valor' => $eventoEsperado['valor'],
                'lancamento' => $eventoEsperado['lancamento'],
                'manual' => false,
            ]);

            Log::info('BonificacaoService - Evento criado', [
                'codbonificacaoevento' => $evento->codbonificacaoevento,
                'codmeta' => $meta->codmeta,
                'codnegocio' => $negocio->codnegocio,
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

            Log::info('BonificacaoService - Evento removido', [
                'codbonificacaoevento' => $eventoExistente->codbonificacaoevento,
                'codmeta' => $meta->codmeta,
                'codnegocio' => $negocio->codnegocio,
                'tipo' => $eventoExistente->tipo,
                'codpessoa' => $eventoExistente->codpessoa,
                'valor' => $eventoExistente->valor,
            ]);
        }
    }

    private static function eventoIgualAoEsperado(BonificacaoEvento $eventoExistente, array $eventoEsperado, int $codunidadenegocio): bool
    {
        return (
            intval($eventoExistente->codunidadenegocio) === intval($codunidadenegocio)
            && abs(static::arredondarValor($eventoExistente->valor - $eventoEsperado['valor'])) < 0.00001
        );
    }

    private static function calcularEventosEsperados(Meta $meta, Negocio $negocio, UnidadeNegocio $unidade, array $bases): array
    {
        $eventos = [];
        $dataLancamento = $negocio->lancamento;
        $alocacao = $negocio->Pdv->alocacao ?? null;

        // VENDA_VENDEDOR
        if (!empty($negocio->codpessoavendedor)) {
            $configuracaoPessoa = static::buscarConfiguracaoPessoaMeta(
                $meta->codmeta,
                $unidade->codunidadenegocio,
                intval($negocio->codpessoavendedor),
                $dataLancamento
            );

            if (!is_null($configuracaoPessoa) && !is_null($configuracaoPessoa->percentualvenda) && $bases['total_venda'] != 0) {
                static::adicionarEventoEsperado(
                    $eventos,
                    static::TIPO_VENDA_VENDEDOR,
                    intval($negocio->codpessoavendedor),
                    $bases['total_venda'] * floatval($configuracaoPessoa->percentualvenda) / 100,
                    $dataLancamento
                );
            }
        }

        // VENDA_XEROX
        if ($bases['total_xerox'] != 0) {
            $pessoasXerox = MetaUnidadeNegocioPessoa::query()
                ->where('codmeta', $meta->codmeta)
                ->where('codunidadenegocio', $unidade->codunidadenegocio)
                ->whereNotNull('percentualxerox')
                ->whereDate('datainicial', '<=', $dataLancamento)
                ->whereDate('datafinal', '>=', $dataLancamento)
                ->orderBy('codpessoa')
                ->get();

            foreach ($pessoasXerox as $pessoaXerox) {
                static::adicionarEventoEsperado(
                    $eventos,
                    static::TIPO_VENDA_XEROX,
                    intval($pessoaXerox->codpessoa),
                    $bases['total_xerox'] * floatval($pessoaXerox->percentualxerox) / 100,
                    $dataLancamento
                );
            }
        }

        // VENDA_LOJA — exclui vendas com alocacao='R'
        if ($bases['total_geral'] != 0 && $alocacao !== static::ALOCACAO_REMOTA) {
            $subgerentes = MetaUnidadeNegocioPessoa::query()
                ->where('codmeta', $meta->codmeta)
                ->where('codunidadenegocio', $unidade->codunidadenegocio)
                ->whereNotNull('percentualsubgerente')
                ->whereDate('datainicial', '<=', $dataLancamento)
                ->whereDate('datafinal', '>=', $dataLancamento)
                ->orderBy('codpessoa')
                ->get();

            foreach ($subgerentes as $subgerente) {
                static::adicionarEventoEsperado(
                    $eventos,
                    static::TIPO_VENDA_LOJA,
                    intval($subgerente->codpessoa),
                    $bases['total_geral'] * floatval($subgerente->percentualsubgerente) / 100,
                    $dataLancamento
                );
            }
        }

        // VENDA_CAIXA — só se alocacao='C'
        if ($alocacao === static::ALOCACAO_CAIXA && !empty($negocio->codusuario) && $bases['total_geral'] != 0) {
            $usuario = Usuario::find($negocio->codusuario);

            if (!empty($usuario) && !empty($usuario->codpessoa)) {
                $codpessoa = intval($usuario->codpessoa);
                $configuracaoPessoa = static::buscarConfiguracaoPessoaMeta($meta->codmeta, $unidade->codunidadenegocio, $codpessoa, $dataLancamento);
                $percentualCaixa = null;

                if (!empty($configuracaoPessoa)) {
                    $percentualCaixa = $configuracaoPessoa->percentualcaixa;
                }

                if (is_null($percentualCaixa)) {
                    $percentualCaixa = static::buscarPercentualCaixaCargo($codpessoa);
                }

                if (!is_null($percentualCaixa)) {
                    static::adicionarEventoEsperado(
                        $eventos,
                        static::TIPO_VENDA_CAIXA,
                        $codpessoa,
                        $bases['total_geral'] * floatval($percentualCaixa) / 100,
                        $dataLancamento
                    );
                }
            }
        }

        ksort($eventos);

        return array_values($eventos);
    }

    private static function adicionarEventoEsperado(array &$eventos, string $tipo, int $codpessoa, float $valor, $lancamento): void
    {
        $chave = static::montarChaveEvento($tipo, $codpessoa);

        if (!isset($eventos[$chave])) {
            $eventos[$chave] = [
                'tipo' => $tipo,
                'codpessoa' => $codpessoa,
                'valor' => static::arredondarValor($valor),
                'lancamento' => $lancamento,
            ];
            return;
        }

        $eventos[$chave]['valor'] = static::arredondarValor($eventos[$chave]['valor'] + $valor);
    }

    private static function buscarConfiguracaoPessoaMeta(int $codmeta, int $codunidadenegocio, int $codpessoa, $dataLancamento): ?MetaUnidadeNegocioPessoa
    {
        return MetaUnidadeNegocioPessoa::query()
            ->where('codmeta', $codmeta)
            ->where('codunidadenegocio', $codunidadenegocio)
            ->where('codpessoa', $codpessoa)
            ->whereDate('datainicial', '<=', $dataLancamento)
            ->whereDate('datafinal', '>=', $dataLancamento)
            ->first();
    }

    private static function buscarPercentualCaixaCargo(int $codpessoa): ?float
    {
        $colaborador = Colaborador::query()
            ->where('codpessoa', $codpessoa)
            ->whereNull('rescisao')
            ->orderByDesc('codcolaborador')
            ->first();

        if (empty($colaborador)) {
            return null;
        }

        $colaboradorCargo = $colaborador->ColaboradorCargoS()
            ->whereNull('fim')
            ->orderByDesc('inicio')
            ->first();

        if (empty($colaboradorCargo) || empty($colaboradorCargo->Cargo)) {
            return null;
        }

        return $colaboradorCargo->Cargo->comissaocaixa;
    }

    private static function identificarUnidadeNegocio(Negocio $negocio): UnidadeNegocio
    {
        if (($negocio->Pdv->alocacao ?? null) === static::ALOCACAO_REMOTA) {
            return UnidadeNegocio::query()
                ->where('descricao', static::DESCRICAO_UNIDADE_REMOTA)
                ->whereNull('inativo')
                ->firstOrFail();
        }

        if (empty($negocio->codfilial)) {
            throw new Exception('Negocio sem filial para identificar unidade de negocio.');
        }

        return UnidadeNegocio::query()
            ->where('codfilial', $negocio->codfilial)
            ->whereNull('inativo')
            ->firstOrFail();
    }

    private static function calcularBasesNegocio(int $codnegocio, bool $ehDevolucao = false): array
    {
        $sql = <<<'SQL'
            select
                coalesce(p.bonificacaoxerox, false) as bonificacaoxerox,
                sum(npb.valortotal) as total
            from tblnegocioprodutobarra npb
            inner join tblprodutobarra pb on pb.codprodutobarra = npb.codprodutobarra
            inner join tblproduto p on p.codproduto = pb.codproduto
            where npb.codnegocio = :codnegocio
              and npb.inativo is null
            group by coalesce(p.bonificacaoxerox, false)
        SQL;

        $registros = DB::select($sql, ['codnegocio' => $codnegocio]);

        $totalVenda = 0.0;
        $totalXerox = 0.0;

        foreach ($registros as $registro) {
            if ($registro->bonificacaoxerox) {
                $totalXerox = floatval($registro->total);
                continue;
            }

            $totalVenda = floatval($registro->total);
        }

        if ($ehDevolucao) {
            $totalVenda = -abs($totalVenda);
            $totalXerox = -abs($totalXerox);
        }

        return [
            'total_venda' => static::arredondarValor($totalVenda),
            'total_xerox' => static::arredondarValor($totalXerox),
            'total_geral' => static::arredondarValor($totalVenda + $totalXerox),
        ];
    }

    private static function montarChaveEvento(string $tipo, int $codpessoa): string
    {
        return $tipo . '|' . $codpessoa;
    }

    private static function arredondarValor(float $valor): float
    {
        return round($valor, 5);
    }

    private static function tiposVenda(): array
    {
        return [
            static::TIPO_VENDA_VENDEDOR,
            static::TIPO_VENDA_CAIXA,
            static::TIPO_VENDA_LOJA,
            static::TIPO_VENDA_XEROX,
        ];
    }
}
