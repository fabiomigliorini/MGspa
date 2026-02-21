<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class PeriodoColaboradorController extends Controller
{
    public function index(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $periodo = Periodo::findOrFail($codperiodo);

        $colaboradores = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->whereHas('Colaborador', function ($q) use ($periodo) {
                $q->where('contratacao', '<=', $periodo->periodofinal)
                    ->where(function ($q2) use ($periodo) {
                        $q2->whereNull('rescisao')
                            ->orWhere('rescisao', '>=', $periodo->periodoinicial);
                    });
            })
            ->with([
                'Colaborador.Pessoa',
                'Colaborador.ColaboradorCargoS' => function ($q) {
                    $q->whereNull('fim')->with('Cargo');
                },
                'PeriodoColaboradorSetorS.Setor.UnidadeNegocio',
                'PeriodoColaboradorSetorS.Setor.TipoSetor',
                'ColaboradorRubricaS.Indicador.Setor',
                'ColaboradorRubricaS.Indicador.UnidadeNegocio',
                'ColaboradorRubricaS.IndicadorCondicao.Setor',
                'ColaboradorRubricaS.IndicadorCondicao.UnidadeNegocio',
            ])
            ->get();

        // Indicadores pessoais (V/C) — por colaborador
        $indicadores = Indicador::where('codperiodo', $codperiodo)
            ->whereNotNull('codcolaborador')
            ->with(['Setor', 'UnidadeNegocio'])
            ->get()
            ->groupBy('codcolaborador');

        // Indicadores de setor (S) — sem codcolaborador, por codsetor
        $indicadoresSetor = Indicador::where('codperiodo', $codperiodo)
            ->whereNull('codcolaborador')
            ->whereNotNull('codsetor')
            ->with(['Setor', 'UnidadeNegocio'])
            ->get()
            ->keyBy('codsetor');

        foreach ($colaboradores as $c) {
            $pessoais = $indicadores->get($c->codcolaborador, collect());

            // Incluir indicadores de setor conforme os setores do colaborador
            $setorInds = collect();
            foreach ($c->PeriodoColaboradorSetorS as $pcs) {
                if ($indicadoresSetor->has($pcs->codsetor)) {
                    $setorInds->push($indicadoresSetor->get($pcs->codsetor));
                }
            }

            $c->indicadores = $pessoais->merge($setorInds);
        }

        return PeriodoColaboradorResource::collection($colaboradores);
    }

    public function encerrar(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $pc = EncerramentoService::encerrar($codperiodocolaborador);
            DB::commit();
            return new PeriodoColaboradorResource($pc);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function estornar(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $pc = EncerramentoService::estornar($codperiodocolaborador);
            DB::commit();
            return new PeriodoColaboradorResource($pc);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function recalcular(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            CalculoRubricaService::calcularColaborador($codperiodocolaborador);
            DB::commit();
            $pc = PeriodoColaborador::find($codperiodocolaborador);
            return new PeriodoColaboradorResource($pc);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
