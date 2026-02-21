<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class PeriodoColaboradorController extends Controller
{
    public function index(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $colaboradores = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->with([
                'Colaborador.Pessoa',
                'Colaborador.ColaboradorCargoS' => function ($q) {
                    $q->whereNull('fim')->with('Cargo');
                },
                'PeriodoColaboradorSetorS.Setor.UnidadeNegocio',
                'PeriodoColaboradorSetorS.Setor.TipoSetor',
                'ColaboradorRubricaS.Indicador',
                'ColaboradorRubricaS.IndicadorCondicao',
            ])
            ->get();

        $indicadores = Indicador::where('codperiodo', $codperiodo)
            ->whereNotNull('codcolaborador')
            ->get()
            ->groupBy('codcolaborador');

        foreach ($colaboradores as $c) {
            $c->indicadores = $indicadores->get($c->codcolaborador, collect());
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
