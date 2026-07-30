<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Filial\Setor;
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
                'Setor',
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

        // Calcula custos por unidade.
        // Traz as unidades ATIVAS + as inativas que tiveram colaboradores no período
        // (mantém o histórico ao visualizar períodos passados).
        $codunidadesComColab = $periodoColaboradores
            ->map(fn($pc) => $pc->Setor?->codunidadenegocio)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $custosPorUnidade = [];
        $unidades = UnidadeNegocio::where(function ($q) use ($codunidadesComColab) {
                $q->whereNull('inativo')
                    ->orWhereIn('codunidadenegocio', $codunidadesComColab);
            })
            ->with('Filial.Empresa')
            ->orderBy('descricao')
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
                $naUnidade = $pc->Setor && $pc->Setor->codunidadenegocio == $un->codunidadenegocio;

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
                'inativo' => $un->inativo,
                'criacao' => $un->criacao,
                'alteracao' => $un->alteracao,
                'codusuariocriacao' => $un->codusuariocriacao,
                'codusuarioalteracao' => $un->codusuarioalteracao,
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

        // Empresas (por CNPJ) com colaboradores no período — botões dinâmicos da
        // planilha do cartão-benefício. Empresa = filial do colaborador (registro
        // mais recente por codpessoa), mesma resolução usada pela planilha.
        $empresasCartao = DB::select("
            SELECT DISTINCT e.codempresa, e.empresa
            FROM tblperiodocolaborador pc
            JOIN tblcolaborador c ON c.codcolaborador = pc.codcolaborador
            JOIN LATERAL (
                SELECT col.codfilial
                FROM tblcolaborador col
                WHERE col.codpessoa = c.codpessoa
                ORDER BY col.codcolaborador DESC
                LIMIT 1
            ) uc ON true
            JOIN tblfilial f ON f.codfilial = uc.codfilial
            JOIN tblempresa e ON e.codempresa = f.codempresa
            WHERE pc.codperiodo = :codperiodo
            ORDER BY e.empresa
        ", ['codperiodo' => $codperiodo]);

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
            'empresascartao' => $empresasCartao,
            'alertas' => $alertas,
        ]);
    }

    public function unidade(int $codperiodo, int $codunidade)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $unidade = UnidadeNegocio::findOrFail($codunidade);

        $indicadores = Indicador::where('codperiodo', $codperiodo)
            ->where('codunidadenegocio', $codunidade)
            ->with('Colaborador.Pessoa')
            ->withCount('IndicadorLancamentoS')
            ->get();

        $colaboradores = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->whereHas('Setor', fn($q) => $q->where('codunidadenegocio', $codunidade))
            ->with([
                'Colaborador.Pessoa',
                'Colaborador.ColaboradorCargoS.Cargo',
                'Setor',
                'ColaboradorRubricaS',
            ])
            ->get();

        // Setores ATIVOS da unidade + os inativos que tiveram colaboradores no período
        // (mantém o histórico ao visualizar períodos passados).
        $codsetoresComColab = $colaboradores->pluck('codsetor')->filter()->unique()->values()->all();

        $setores = Setor::where('codunidadenegocio', $codunidade)
            ->where(function ($q) use ($codsetoresComColab) {
                $q->whereNull('inativo')
                    ->orWhereIn('codsetor', $codsetoresComColab);
            })
            ->orderBy('setor')
            ->get();

        $setoresOut = $setores->map(function ($setor) use ($indicadores, $colaboradores) {
            $indSetor = $indicadores->where('codsetor', $setor->codsetor);
            $colabSetor = $colaboradores->where('codsetor', $setor->codsetor);

            return [
                'codsetor' => $setor->codsetor,
                'descricao' => $setor->setor,
                'setor' => $setor->setor,
                'codunidadenegocio' => $setor->codunidadenegocio,
                'codtiposetor' => $setor->codtiposetor,
                'indicadorvendedor' => $setor->indicadorvendedor,
                'indicadorcaixa' => $setor->indicadorcaixa,
                'indicadorcoletivo' => $setor->indicadorcoletivo,
                'inativo' => $setor->inativo,
                'criacao' => $setor->criacao,
                'alteracao' => $setor->alteracao,
                'codusuariocriacao' => $setor->codusuariocriacao,
                'codusuarioalteracao' => $setor->codusuarioalteracao,
                'indicadores' => $indSetor->map(fn($i) => [
                    'codindicador' => $i->codindicador,
                    'tipo' => $i->tipo,
                    'colaborador_nome' => $i->Colaborador?->Pessoa?->fantasia,
                    'meta' => $i->meta,
                    'valoracumulado' => $i->valoracumulado,
                    'atingimento' => $i->meta ? round($i->valoracumulado / $i->meta * 100, 2) : null,
                    'lancamentos_count' => $i->indicador_lancamento_s_count,
                ])->values(),
                'colaboradores' => $colabSetor->map(function ($pc) use ($indicadores) {
                    $cargos = $pc->Colaborador?->ColaboradorCargoS?->sortByDesc('inicio');
                    $cargoAtivo = $cargos?->firstWhere('fim', null) ?? $cargos?->first();

                    // Só os indicadores que este colaborador TEM RUBRICA amarrada
                    // (via codindicador ou codindicadorcondicao das ColaboradorRubricaS).
                    // Nada de adivinhar por tipo/codcolaborador.
                    $codsIndicadorRubrica = $pc->ColaboradorRubricaS
                        ->flatMap(fn($cr) => [$cr->codindicador, $cr->codindicadorcondicao])
                        ->filter()
                        ->unique();

                    $indsColab = $indicadores
                        ->filter(fn($i) => $codsIndicadorRubrica->contains($i->codindicador))
                        ->sortBy('tipo')
                        ->values();

                    return [
                        'codperiodocolaborador' => $pc->codperiodocolaborador,
                        'codcolaborador' => $pc->codcolaborador,
                        'nome' => $pc->Colaborador?->Pessoa?->fantasia,
                        'cargo' => $cargoAtivo?->Cargo?->cargo,
                        'gestor' => $pc->gestor,
                        'status' => $pc->status,
                        'variaveis' => $pc->valortotal,
                        'encerramento' => $pc->encerramento,
                        'codtitulo' => $pc->codtitulo,
                        'indicadores' => $indsColab->map(fn($i) => [
                            'codindicador' => $i->codindicador,
                            'tipo' => $i->tipo,
                            'vendas' => $i->valoracumulado,
                            'meta' => $i->meta,
                            'atingimento' => $i->meta ? round($i->valoracumulado / $i->meta * 100, 2) : null,
                        ])->values(),
                    ];
                })->values(),
            ];
        });

        $indUnidade = $indicadores->firstWhere('tipo', ProcessarVendaService::TIPO_UNIDADE);

        return response()->json([
            'codunidadenegocio' => $unidade->codunidadenegocio,
            'descricao' => $unidade->descricao,
            'vendas' => $indUnidade?->valoracumulado ?? 0,
            'meta' => $indUnidade?->meta,
            'atingimento' => $indUnidade && $indUnidade->meta
                ? round($indUnidade->valoracumulado / $indUnidade->meta * 100, 2)
                : null,
            'setores' => $setoresOut->values(),
        ]);
    }

    protected static function gerarAlertas(int $codperiodo): array
    {
        $alertas = [];

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
