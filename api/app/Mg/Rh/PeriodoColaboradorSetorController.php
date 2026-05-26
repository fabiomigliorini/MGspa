<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class PeriodoColaboradorSetorController extends Controller
{
    public function store(int $codperiodocolaborador, PeriodoColaboradorSetorStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $pcs = PeriodoColaboradorSetor::create(array_merge(
                $request->validated(),
                ['codperiodocolaborador' => $codperiodocolaborador]
            ));
            DB::commit();
            return new PeriodoColaboradorSetorResource($pcs);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function update(int $codperiodocolaboradorsetor, PeriodoColaboradorSetorUpdateRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $pcs = PeriodoColaboradorSetor::findOrFail($codperiodocolaboradorsetor);
            $pcs->fill($request->validated());
            $pcs->save();
            DB::commit();
            return new PeriodoColaboradorSetorResource($pcs);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codperiodocolaboradorsetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $pcs = PeriodoColaboradorSetor::findOrFail($codperiodocolaboradorsetor);
            ColaboradorRubrica::where('codperiodocolaboradorsetor', $codperiodocolaboradorsetor)->delete();
            $pcs->delete();
            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
