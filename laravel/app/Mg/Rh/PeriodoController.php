<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class PeriodoController extends Controller
{
    public function index()
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $periodos = Periodo::orderBy('periodofinal', 'desc')->get();

        foreach ($periodos as $p) {
            $p->total_colaboradores = $p->PeriodoColaboradorS()->count();
            $p->colaboradores_encerrados = $p->PeriodoColaboradorS()
                ->where('status', PeriodoService::STATUS_COLABORADOR_ENCERRADO)->count();
            $p->total_variaveis = $p->PeriodoColaboradorS()->sum('valortotal');
        }

        return PeriodoResource::collection($periodos);
    }

    public function store(PeriodoStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $periodo = PeriodoService::criar($request->validated());
            DB::commit();
            return new PeriodoResource($periodo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function duplicar(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $periodo = PeriodoService::duplicarDoAnterior($codperiodo);
            DB::commit();
            return new PeriodoResource($periodo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function fechar(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $periodo = PeriodoService::fechar($codperiodo);
            DB::commit();
            return new PeriodoResource($periodo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function reabrir(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $periodo = PeriodoService::reabrir($codperiodo);
            DB::commit();
            return new PeriodoResource($periodo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
