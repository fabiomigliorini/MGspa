<?php

namespace Mg\Rh;

use Mg\Colaborador\ColaboradorCargo;
use Mg\Filial\UnidadeNegocio;

/**
 * Custo fixo de folha por colaborador dentro de um período.
 *
 * Fonte única de verdade para salário / adicional / encargos: o Resumão, a aba
 * da filial e a tela do colaborador consomem este mesmo mapa, e por isso os
 * quatro níveis de agregação (colaborador → setor → unidade → empresa) fecham.
 *
 * ARREDONDAMENTO: tudo é arredondado UMA vez, aqui, no nível do colaborador.
 * Todo agregado acima disso é soma pura de valores já arredondados. Antes, os
 * encargos eram arredondados no agregado da unidade, o que fazia a soma dos
 * setores divergir do total da unidade em centavos.
 *
 * RECORTE: colaborador sem Setor não pertence a nenhuma unidade e por isso fica
 * de fora dos totais por unidade — inclusive do Total Geral do Resumão, que é a
 * soma das unidades. O mapa devolve o custo dele mesmo assim, com
 * codunidadenegocio null; quem agrega por unidade simplesmente não o encontra.
 */
class CustoColaboradorService
{
    /**
     * Mapa de custo indexado por codperiodocolaborador.
     *
     * Faz as próprias queries de propósito: `PeriodoColaboradorController@index`
     * carrega ColaboradorCargoS já filtrado por `whereNull('fim')`, enquanto o
     * DashboardController carrega tudo e cai no cargo mais recente quando não há
     * nenhum em aberto. Depender do eager-load do chamador faria o mesmo
     * colaborador ter salário 0 numa tela e o salário histórico na outra.
     *
     * @return array<int, array{
     *   salario: float, adicional: float, encargos: float, custo: float,
     *   fatorencargos: float, codunidadenegocio: ?int
     * }>
     */
    public static function mapaPorPeriodo(int $codperiodo, ?int $codunidadenegocio = null): array
    {
        $query = PeriodoColaborador::where('codperiodo', $codperiodo)->with('Setor');

        if ($codunidadenegocio !== null) {
            $query->whereHas('Setor', fn ($q) => $q->where('codunidadenegocio', $codunidadenegocio));
        }

        $periodoColaboradores = $query->get();

        if ($periodoColaboradores->isEmpty()) {
            return [];
        }

        $cargoMap = static::cargosAtivos($periodoColaboradores->pluck('codcolaborador')->filter()->unique()->all());
        $fatorMap = static::fatoresEncargos();

        $mapa = [];

        foreach ($periodoColaboradores as $pc) {
            $codunidade = $pc->Setor?->codunidadenegocio;
            $fator = $fatorMap[$codunidade] ?? 0;

            $cargo = $cargoMap[$pc->codcolaborador] ?? null;

            // Salário do vínculo, com o do cargo como piso quando o vínculo não define.
            $salario = $cargo ? ($cargo->salario ?: ($cargo->Cargo->salario ?? 0)) : 0;
            $adicional = $salario * (($cargo?->Cargo?->adicional) ?? 0) / 100;

            $salario = round($salario, 2);
            $adicional = round($adicional, 2);
            $encargos = round(($salario + $adicional) * $fator, 2);

            $mapa[$pc->codperiodocolaborador] = [
                'salario' => $salario,
                'adicional' => $adicional,
                'encargos' => $encargos,
                'custo' => round($salario + $adicional + $encargos, 2),
                'fatorencargos' => (float) $fator,
                'codunidadenegocio' => $codunidade,
            ];
        }

        return $mapa;
    }

    /**
     * Cargo ativo de cada colaborador: o vínculo em aberto mais recente; na
     * falta de qualquer um em aberto, o mais recente encerrado (mantém o
     * histórico coerente ao abrir períodos passados).
     *
     * @return array<int, ColaboradorCargo>
     */
    protected static function cargosAtivos(array $codcolaboradores): array
    {
        if (empty($codcolaboradores)) {
            return [];
        }

        $cargos = ColaboradorCargo::whereIn('codcolaborador', $codcolaboradores)
            ->with('Cargo')
            ->get()
            ->sortByDesc('inicio')
            ->groupBy('codcolaborador');

        $mapa = [];
        foreach ($cargos as $codcolaborador => $doColaborador) {
            $mapa[$codcolaborador] = $doColaborador->firstWhere('fim', null) ?? $doColaborador->first();
        }

        return $mapa;
    }

    /**
     * Fator de encargos da empresa de cada unidade de negócio.
     *
     * @return array<int, float>
     */
    protected static function fatoresEncargos(): array
    {
        return UnidadeNegocio::with('Filial.Empresa')
            ->get()
            ->mapWithKeys(fn ($un) => [
                $un->codunidadenegocio => (float) ($un->Filial->Empresa->fatorencargos ?? 0),
            ])
            ->all();
    }
}
