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
        $pc = PeriodoColaborador::findOrFail($codperiodocolaborador);

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

            $rubrica->valorcalculado = round($valor, 2);
            $rubrica->save();
        }

        // Atualiza valortotal
        $pc->valortotal = ColaboradorRubrica::where('codperiodocolaborador', $codperiodocolaborador)->sum('valorcalculado');
        $pc->save();
    }

    /**
     * Rateio dos indicadores COLETIVOS: quanto do pool do setor já está
     * distribuído em comissão. Devolve, por indicador,
     * ['pool' => 6.0, 'soma' => 4.68, 'distribuido' => 78.0].
     *
     * O pool não tem coluna própria — vem do catálogo, do "Percentual Padrão"
     * da tblrubrica de onde a rubrica saiu, que para um setor coletivo é o
     * percentual CHEIO do setor (ex.: 6% da venda do xerox). Já o percentual
     * gravado na rubrica do colaborador é o efetivo, com a fatia da pessoa
     * embutida — o rh_rubricas.sql colapsou o rateio dentro dele ("50% de 5%
     * = 2,5%"). Daí: fatia da pessoa = percentual / pool, e o quanto o setor
     * distribuiu = soma dos percentuais / pool.
     *
     * Sem pool no catálogo devolve 'distribuido' nulo: a tela simplesmente não
     * mostra o rateio, em vez de inventar um número.
     *
     * Uma query para a lista toda — o cockpit de uma unidade tem dezenas de
     * indicadores e um accessor no model viraria N+1.
     *
     * Conta só rubrica concedida: a não concedida vale zero no cálculo, então
     * somá-la faria o setor parecer distribuído sem ninguém receber.
     */
    public static function rateioDistribuido(array $codsIndicador): array
    {
        if (empty($codsIndicador)) {
            return [];
        }

        $linhas = ColaboradorRubrica::query()
            ->leftJoin('tblrubrica', 'tblrubrica.codrubrica', '=', 'tblcolaboradorrubrica.codrubrica')
            ->whereIn('tblcolaboradorrubrica.codindicador', $codsIndicador)
            ->where('tblcolaboradorrubrica.tipovalor', self::TIPO_PERCENTUAL)
            ->where('tblcolaboradorrubrica.concedido', true)
            ->whereNotNull('tblcolaboradorrubrica.percentual')
            ->groupBy('tblcolaboradorrubrica.codindicador')
            ->selectRaw('tblcolaboradorrubrica.codindicador as codindicador')
            ->selectRaw('sum(tblcolaboradorrubrica.percentual) as soma')
            ->selectRaw('max(tblrubrica.valorpadrao) as pool')
            ->get();

        $ret = [];

        foreach ($linhas as $linha) {
            $pool = (float) $linha->pool;
            $soma = (float) $linha->soma;

            $ret[(int) $linha->codindicador] = [
                'pool' => $pool ?: null,
                'soma' => round($soma, 6),
                'distribuido' => $pool ? round($soma / $pool * 100, 3) : null,
            ];
        }

        return $ret;
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
