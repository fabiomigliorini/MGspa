<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Filial\Empresa;
use Mg\Filial\UnidadeNegocio;
use Mg\Usuario\Autorizador;

class DashboardController extends Controller
{
    public function index(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $periodo = Periodo::findOrFail($codperiodo);

        $totalColaboradores = PeriodoColaborador::where('codperiodo', $codperiodo)->count();
        $colaboradoresEncerrados = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->where('status', PeriodoService::STATUS_COLABORADOR_ENCERRADO)->count();
        $totalVariaveis = PeriodoColaborador::where('codperiodo', $codperiodo)->sum('valortotal');

        $indicadoresUnidade = Indicador::where('codperiodo', $codperiodo)
            ->where('tipo', ProcessarVendaService::TIPO_UNIDADE)
            ->with('UnidadeNegocio')
            ->get();

        $custosPorUnidade = [];
        $unidades = UnidadeNegocio::whereNull('inativo')->get();

        foreach ($unidades as $un) {
            $totalVarUnidade = DB::table('tblperiodocolaborador as pc')
                ->join('tblperiodocolaboradorsetor as pcs', 'pcs.codperiodocolaborador', '=', 'pc.codperiodocolaborador')
                ->join('tblsetor as s', 's.codsetor', '=', 'pcs.codsetor')
                ->where('pc.codperiodo', $codperiodo)
                ->where('s.codunidadenegocio', $un->codunidadenegocio)
                ->sum('pc.valortotal');

            $fatorEncargos = 0;
            if ($un->codfilial) {
                $filial = DB::table('tblfilial')->where('codfilial', $un->codfilial)->first();
                if ($filial) {
                    $empresa = Empresa::find($filial->codempresa);
                    $fatorEncargos = $empresa ? $empresa->fatorencargos : 0;
                }
            }

            $indicadorUnidade = $indicadoresUnidade->firstWhere('codunidadenegocio', $un->codunidadenegocio);

            $custosPorUnidade[] = [
                'codunidadenegocio' => $un->codunidadenegocio,
                'descricao' => $un->descricao,
                'vendas' => $indicadorUnidade ? $indicadorUnidade->valoracumulado : 0,
                'meta' => $indicadorUnidade ? $indicadorUnidade->meta : null,
                'totalvariaveis' => round($totalVarUnidade, 2),
                'encargosestimados' => round($totalVarUnidade * $fatorEncargos, 2),
                'fatorencargos' => $fatorEncargos,
            ];
        }

        $alertas = static::gerarAlertas($codperiodo);

        return response()->json([
            'periodo' => new PeriodoResource($periodo),
            'totalcolaboradores' => $totalColaboradores,
            'colaboradoresencerrados' => $colaboradoresEncerrados,
            'totalvariaveis' => round($totalVariaveis, 2),
            'unidades' => $custosPorUnidade,
            'alertas' => $alertas,
        ]);
    }

    protected static function gerarAlertas(int $codperiodo): array
    {
        $alertas = [];

        $semSetor = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->whereDoesntHave('PeriodoColaboradorSetorS')
            ->with('Colaborador.Pessoa')
            ->get();

        foreach ($semSetor as $pc) {
            $alertas[] = [
                'tipo' => 'sem_setor',
                'mensagem' => ($pc->Colaborador->Pessoa->fantasia ?? 'Colaborador') . ' sem vínculo com setor',
                'codperiodocolaborador' => $pc->codperiodocolaborador,
            ];
        }

        $multiplos = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->has('PeriodoColaboradorSetorS', '>', 1)
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
