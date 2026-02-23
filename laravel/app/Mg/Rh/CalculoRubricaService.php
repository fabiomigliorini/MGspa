<?php

namespace Mg\Rh;

class CalculoRubricaService
{
    const TIPO_PERCENTUAL = 'P';
    const TIPO_FIXO = 'F';
    const CONDICAO_META = 'M';
    const CONDICAO_RANKING = 'R';

    const TIPO_DESCRICAO = [
        self::TIPO_PERCENTUAL => 'Percentual',
        self::TIPO_FIXO => 'Fixo',
    ];

    const CONDICAO_DESCRICAO = [
        self::CONDICAO_META => 'Meta',
        self::CONDICAO_RANKING => 'Ranking',
    ];

    public static function calcular(int $codperiodo): void
    {
        $periodosColaborador = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->where('status', PeriodoService::STATUS_COLABORADOR_ABERTO)
            ->get();

        foreach ($periodosColaborador as $pc) {
            static::calcularColaborador($pc->codperiodocolaborador);
        }
    }

    public static function calcularColaborador(int $codperiodocolaborador): void
    {
        $pc = PeriodoColaborador::with(['PeriodoColaboradorSetorS', 'Periodo'])->findOrFail($codperiodocolaborador);
        $diasuteis = $pc->Periodo->diasuteis;

        // Ordenação: sem condição → meta → ranking
        $rubricas = ColaboradorRubrica::where('codperiodocolaborador', $codperiodocolaborador)
            ->orderByRaw("CASE WHEN tipocondicao IS NULL THEN 0 WHEN tipocondicao = 'M' THEN 1 WHEN tipocondicao = 'R' THEN 2 END")
            ->get();

        foreach ($rubricas as $rubrica) {
            // Não concedido → zero
            if (!$rubrica->concedido) {
                $rubrica->valorcalculado = 0;
                $rubrica->save();
                continue;
            }

            // Verifica condição
            if ($rubrica->tipocondicao === self::CONDICAO_META) {
                if (!static::metaAtingida($rubrica)) {
                    $rubrica->valorcalculado = 0;
                    $rubrica->save();
                    continue;
                }
            } elseif ($rubrica->tipocondicao === self::CONDICAO_RANKING) {
                if (!static::ehPrimeiroNoRanking($rubrica)) {
                    $rubrica->valorcalculado = 0;
                    $rubrica->save();
                    continue;
                }
            }

            // Calcula valor base
            if ($rubrica->tipovalor === self::TIPO_PERCENTUAL) {
                $indicador = $rubrica->codindicador ? Indicador::find($rubrica->codindicador) : null;
                if (!$indicador) {
                    $rubrica->valorcalculado = 0;
                    $rubrica->save();
                    continue;
                }

                // Indicador coletivo (setor): rateio ponderado
                if ($indicador->tipo === ProcessarVendaService::TIPO_SETOR) {
                    $vinculo = $rubrica->codperiodocolaboradorsetor
                        ? PeriodoColaboradorSetor::find($rubrica->codperiodocolaboradorsetor)
                        : $pc->PeriodoColaboradorSetorS->first();

                    if (!$vinculo) {
                        $rubrica->valorcalculado = 0;
                        $rubrica->save();
                        continue;
                    }

                    $valorRateio = static::calcularRateio($vinculo, $indicador->valoracumulado, $pc->Periodo->codperiodo);
                    $valor = $valorRateio * ($rubrica->percentual / 100);
                } else {
                    $valor = $indicador->valoracumulado * ($rubrica->percentual / 100);
                }
            } else {
                // Fixo
                $valor = $rubrica->valorfixo ?? 0;
            }

            // Desconto de absenteísmo
            if ($rubrica->descontaabsenteismo && $diasuteis > 0) {
                $vinculo = $rubrica->codperiodocolaboradorsetor
                    ? PeriodoColaboradorSetor::find($rubrica->codperiodocolaboradorsetor)
                    : $pc->PeriodoColaboradorSetorS->first();

                $diastrabalhados = $vinculo ? $vinculo->diastrabalhados : 0;

                if ($diastrabalhados > 0) {
                    $valor = $valor * ($diastrabalhados / $diasuteis);
                } else {
                    $valor = 0;
                }
            }

            $rubrica->valorcalculado = round($valor, 2);
            $rubrica->save();
        }

        // Atualiza valortotal
        $pc->valortotal = ColaboradorRubrica::where('codperiodocolaborador', $codperiodocolaborador)->sum('valorcalculado');
        $pc->save();
    }

    protected static function metaAtingida(ColaboradorRubrica $rubrica): bool
    {
        $indicador = $rubrica->codindicadorcondicao ? Indicador::find($rubrica->codindicadorcondicao) : null;
        if (!$indicador) {
            return false;
        }

        if (!$indicador->meta || $indicador->meta == 0) {
            return false;
        }

        return $indicador->valoracumulado >= $indicador->meta;
    }

    protected static function ehPrimeiroNoRanking(ColaboradorRubrica $rubrica): bool
    {
        $indicadorCondicao = $rubrica->codindicadorcondicao ? Indicador::find($rubrica->codindicadorcondicao) : null;
        if (!$indicadorCondicao) {
            return false;
        }

        $codcolaborador = $rubrica->PeriodoColaborador->codcolaborador;

        $query = Indicador::where('codperiodo', $indicadorCondicao->codperiodo)
            ->where('tipo', $indicadorCondicao->tipo)
            ->whereNotNull('codcolaborador');

        if ($indicadorCondicao->codsetor) {
            $query->where('codsetor', $indicadorCondicao->codsetor);
        } elseif ($indicadorCondicao->codunidadenegocio) {
            $query->where('codunidadenegocio', $indicadorCondicao->codunidadenegocio);
        }

        $primeiro = $query->orderByDesc('valoracumulado')
            ->orderBy('codcolaborador')
            ->first();

        return $primeiro && $primeiro->codcolaborador === $codcolaborador;
    }

    protected static function calcularRateio(PeriodoColaboradorSetor $vinculo, float $valorIndicador, int $codperiodo): float
    {
        $todosVinculos = PeriodoColaboradorSetor::where('codsetor', $vinculo->codsetor)
            ->whereHas('PeriodoColaborador', function ($q) use ($codperiodo) {
                $q->where('codperiodo', $codperiodo)
                    ->where('status', PeriodoService::STATUS_COLABORADOR_ABERTO);
            })
            ->get();

        $totalPontos = 0;
        $pontosColaborador = 0;

        foreach ($todosVinculos as $v) {
            $pontos = $v->percentualrateio * $v->diastrabalhados;
            $totalPontos += $pontos;
            if ($v->codperiodocolaboradorsetor === $vinculo->codperiodocolaboradorsetor) {
                $pontosColaborador = $pontos;
            }
        }

        if ($totalPontos == 0) {
            return 0;
        }

        return ($pontosColaborador / $totalPontos) * $valorIndicador;
    }
}
