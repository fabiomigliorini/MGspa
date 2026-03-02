<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Mg\Filial\UnidadeNegocio;
use Mg\Usuario\Autorizador;

class DashboardController extends Controller
{
    public function index(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $periodo = Periodo::findOrFail($codperiodo);

        // Carrega colaboradores com cargo e setores
        $periodoColaboradores = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->with([
                'Colaborador.ColaboradorCargoS.Cargo',
                'PeriodoColaboradorSetorS.Setor',
            ])
            ->get();

        $totalColaboradores = $periodoColaboradores->count();
        $colaboradoresEncerrados = $periodoColaboradores
            ->where('status', PeriodoService::STATUS_COLABORADOR_ENCERRADO)
            ->count();

        // Mapa salário/adicional por colaborador
        $salarioMap = [];
        foreach ($periodoColaboradores as $pc) {
            $colaborador = $pc->Colaborador;
            if (!$colaborador) {
                $salarioMap[$pc->codperiodocolaborador] = ['salario' => 0, 'adicional' => 0];
                continue;
            }

            // Cargo ativo: sem data fim, mais recente por início
            $cargos = $colaborador->ColaboradorCargoS->sortByDesc('inicio');
            $cargoAtivo = $cargos->firstWhere('fim', null) ?? $cargos->first();

            $salario = 0;
            $adicionalPct = 0;

            if ($cargoAtivo) {
                // Salário: do ColaboradorCargo, fallback do Cargo
                $salario = $cargoAtivo->salario ?: ($cargoAtivo->Cargo->salario ?? 0);
                $adicionalPct = $cargoAtivo->Cargo->adicional ?? 0;
            }

            $adicionalValor = $salario * $adicionalPct / 100;

            $salarioMap[$pc->codperiodocolaborador] = [
                'salario' => round($salario, 2),
                'adicional' => round($adicionalValor, 2),
            ];
        }

        // Indicadores de vendas por unidade
        $indicadoresUnidade = Indicador::where('codperiodo', $codperiodo)
            ->where('tipo', ProcessarVendaService::TIPO_UNIDADE)
            ->with('UnidadeNegocio')
            ->get();

        // Calcula custos por unidade
        $custosPorUnidade = [];
        $unidades = UnidadeNegocio::whereNull('inativo')
            ->with('Filial.Empresa')
            ->get();

        foreach ($unidades as $un) {
            // Fator de encargos da empresa
            $empresa = $un->Filial->Empresa ?? null;
            $fatorEncargos = $empresa ? $empresa->fatorencargos : 0;

            // Colaboradores únicos nesta unidade
            $codColabsVistos = [];
            $totalSalarioUnidade = 0;
            $totalAdicionalUnidade = 0;
            $totalVarUnidade = 0;

            foreach ($periodoColaboradores as $pc) {
                $naUnidade = $pc->PeriodoColaboradorSetorS->contains(function ($pcs) use ($un) {
                    return $pcs->Setor && $pcs->Setor->codunidadenegocio == $un->codunidadenegocio;
                });

                if (!$naUnidade) continue;
                if (in_array($pc->codperiodocolaborador, $codColabsVistos)) continue;
                $codColabsVistos[] = $pc->codperiodocolaborador;

                $info = $salarioMap[$pc->codperiodocolaborador] ?? ['salario' => 0, 'adicional' => 0];
                $totalSalarioUnidade += $info['salario'];
                $totalAdicionalUnidade += $info['adicional'];
                $totalVarUnidade += $pc->valortotal ?? 0;
            }

            $baseEncargos = $totalSalarioUnidade + $totalAdicionalUnidade;
            $totalEncargosUnidade = round($baseEncargos * $fatorEncargos, 2);

            $indicadorUnidade = $indicadoresUnidade->firstWhere('codunidadenegocio', $un->codunidadenegocio);

            $custosPorUnidade[] = [
                'codunidadenegocio' => $un->codunidadenegocio,
                'descricao' => $un->descricao,
                'codfilial' => $un->Filial->codfilial ?? null,
                'codempresa' => $empresa ? $empresa->codempresa : null,
                'empresa' => $empresa ? $empresa->empresa : null,
                'vendas' => $indicadorUnidade ? $indicadorUnidade->valoracumulado : 0,
                'meta' => $indicadorUnidade ? $indicadorUnidade->meta : null,
                'codindicador' => $indicadorUnidade ? $indicadorUnidade->codindicador : null,
                'totalsalario' => round($totalSalarioUnidade, 2),
                'totaladicional' => round($totalAdicionalUnidade, 2),
                'totalencargos' => $totalEncargosUnidade,
                'totalvariaveis' => round($totalVarUnidade, 2),
                'total' => round($totalSalarioUnidade + $totalAdicionalUnidade + $totalEncargosUnidade + $totalVarUnidade, 2),
                'fatorencargos' => $fatorEncargos,
            ];
        }

        // Totais gerais (soma das unidades)
        $totalSalarioGeral = 0;
        $totalAdicionalGeral = 0;
        $totalEncargosGeral = 0;
        $totalVariaveisGeral = 0;
        $totalGeral = 0;

        foreach ($custosPorUnidade as $cpu) {
            $totalSalarioGeral += $cpu['totalsalario'];
            $totalAdicionalGeral += $cpu['totaladicional'];
            $totalEncargosGeral += $cpu['totalencargos'];
            $totalVariaveisGeral += $cpu['totalvariaveis'];
            $totalGeral += $cpu['total'];
        }

        $alertas = static::gerarAlertas($codperiodo);

        return response()->json([
            'periodo' => new PeriodoResource($periodo),
            'totalcolaboradores' => $totalColaboradores,
            'colaboradoresencerrados' => $colaboradoresEncerrados,
            'totalsalario' => round($totalSalarioGeral, 2),
            'totaladicional' => round($totalAdicionalGeral, 2),
            'totalencargos' => round($totalEncargosGeral, 2),
            'totalvariaveis' => round($totalVariaveisGeral, 2),
            'total' => round($totalGeral, 2),
            'unidades' => $custosPorUnidade,
            'alertas' => $alertas,
        ]);
    }

    protected static function gerarAlertas(int $codperiodo): array
    {
        $alertas = [];

        $multiplos = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->has('PeriodoColaboradorSetorS', '>', 1)
            ->whereHas('Colaborador', fn($q) => $q->whereNull('rescisao'))
            ->with('Colaborador.Pessoa')
            ->get();

        foreach ($multiplos as $pc) {
            $alertas[] = [
                'tipo' => 'multiplos_setores',
                'mensagem' => ($pc->Colaborador->Pessoa->fantasia ?? 'Colaborador') . ' vinculado a múltiplos setores',
                'codperiodocolaborador' => $pc->codperiodocolaborador,
            ];
        }

        $semMeta = Indicador::where('codperiodo', $codperiodo)
            ->where('tipo', ProcessarVendaService::TIPO_UNIDADE)
            ->whereNull('meta')
            ->with('UnidadeNegocio')
            ->get();

        foreach ($semMeta as $ind) {
            $alertas[] = [
                'tipo' => 'sem_meta',
                'mensagem' => 'Meta não definida para ' . ($ind->UnidadeNegocio->descricao ?? 'unidade'),
                'codindicador' => $ind->codindicador,
            ];
        }

        return $alertas;
    }
}
