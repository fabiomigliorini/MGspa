<?php

namespace Mg\Rh;

class CalculoRubricaService
{
    const TIPO_PERCENTUAL = 'P';
    const TIPO_FIXO = 'F';
    const TIPO_QUANTIDADE = 'Q';
    const CONDICAO_META = 'M';
    const CONDICAO_RANKING = 'R';

    const TIPO_DESCRICAO = [
        self::TIPO_PERCENTUAL => 'Percentual',
        self::TIPO_FIXO => 'Fixo',
        self::TIPO_QUANTIDADE => 'Unitário × Quantidade',
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
        $pc = PeriodoColaborador::with('Periodo')->findOrFail($codperiodocolaborador);
        $diasuteis = $pc->Periodo->diasuteis;

        // Ordenação: sem condição → meta → ranking
        $rubricas = ColaboradorRubrica::with('Rubrica')
            ->where('codperiodocolaborador', $codperiodocolaborador)
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

            // Calcula valor
            if ($rubrica->tipovalor === self::TIPO_PERCENTUAL) {
                // Percentual efetivo direto sobre o indicador (sem rateio)
                $indicador = $rubrica->codindicador ? Indicador::find($rubrica->codindicador) : null;
                $valor = $indicador ? $indicador->valoracumulado * ($rubrica->percentual / 100) : 0;
            } elseif ($rubrica->tipovalor === self::TIPO_QUANTIDADE) {
                // Unitário × Quantidade (ex.: marmita R$15 × 26 dias)
                $valor = ($rubrica->valorunitario ?? 0) * ($rubrica->quantidade ?? 0);
            } else {
                // Fixo
                $valor = $rubrica->valorfixo ?? 0;
            }

            $valor *= static::fatorDias(
                $rubrica->diastrabalhados,
                $pc->faltas,
                $rubrica->Rubrica?->descontaabsenteismo,
                $diasuteis
            );

            $rubrica->valorcalculado = round($valor, 2);
            $rubrica->save();
        }

        // Atualiza valortotal
        $pc->valortotal = ColaboradorRubrica::where('codperiodocolaborador', $codperiodocolaborador)->sum('valorcalculado');
        $pc->save();
    }

    /**
     * Quanto da rubrica a pessoa leva pelos dias trabalhados, de 0 a 1.
     *
     * Dias gravados na própria rubrica — quem cobre alguns dias num setor,
     * como o xerox — pagam dias / dias úteis e têm precedência: a pessoa
     * recebe a fatia de quem trabalha o período inteiro, proporcional aos
     * dias (TASK-262). Sem eles, a rubrica do catálogo marcada "desconta
     * absenteísmo" perde as faltas que o RH lançou no acerto. Nulo é o
     * período inteiro: fator 1.
     */
    public static function fatorDias(?int $diastrabalhados, ?int $faltas, ?bool $descontaabsenteismo, ?int $diasuteis): float
    {
        if (!$diasuteis || $diasuteis <= 0) {
            return 1;
        }

        if ($diastrabalhados !== null) {
            return min($diastrabalhados, $diasuteis) / $diasuteis;
        }

        if ($descontaabsenteismo && $faltas > 0) {
            return max($diasuteis - $faltas, 0) / $diasuteis;
        }

        return 1;
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
}
