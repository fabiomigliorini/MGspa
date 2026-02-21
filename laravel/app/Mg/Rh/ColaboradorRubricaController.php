<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class ColaboradorRubricaController extends Controller
{
    public function store(int $codperiodocolaborador, ColaboradorRubricaStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $rubrica = ColaboradorRubrica::create(array_merge(
                $request->validated(),
                ['codperiodocolaborador' => $codperiodocolaborador, 'valorcalculado' => 0]
            ));

            CalculoRubricaService::calcularColaborador($codperiodocolaborador);

            DB::commit();
            return new ColaboradorRubricaResource($rubrica->refresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function update(int $codcolaboradorrubrica, ColaboradorRubricaUpdateRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $rubrica = ColaboradorRubrica::findOrFail($codcolaboradorrubrica);
            $rubrica->fill($request->validated());
            $rubrica->save();

            CalculoRubricaService::calcularColaborador($rubrica->codperiodocolaborador);

            DB::commit();
            return new ColaboradorRubricaResource($rubrica->refresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codcolaboradorrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $rubrica = ColaboradorRubrica::findOrFail($codcolaboradorrubrica);
            $codperiodocolaborador = $rubrica->codperiodocolaborador;
            $rubrica->delete();

            CalculoRubricaService::calcularColaborador($codperiodocolaborador);

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function toggleConcedido(int $codcolaboradorrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $rubrica = ColaboradorRubrica::findOrFail($codcolaboradorrubrica);
            $rubrica->concedido = !$rubrica->concedido;
            $rubrica->save();

            CalculoRubricaService::calcularColaborador($rubrica->codperiodocolaborador);

            DB::commit();
            return new ColaboradorRubricaResource($rubrica->refresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
