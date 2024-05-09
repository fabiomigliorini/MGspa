<?php

namespace Mg\Colaborador;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ColaboradorCargoController extends MgController
{

    public function create(Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $data = $request->all();
        DB::beginTransaction();
        $cc = ColaboradorCargoService::create($data);
        DB::commit();
        return new ColaboradorResource($cc->Colaborador);
    }

    public function show(Request $request, $codcolaborador, $codcolaboradorcargo)
    {
        $colaborador = ColaboradorCargo::findOrFail($codcolaboradorcargo);
        return new ColaboradorCargoResource($colaborador);
    }

    public function update(Request $request, $codcolaborador, $codcolaboradorcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $data = $request->all();
        $colaborador = ColaboradorCargo::findOrFail($codcolaboradorcargo);
        $colaborador = ColaboradorCargoService::update($colaborador, $data);

        return new ColaboradorCargoResource($colaborador);
    }

    public function delete(Request $request, $codcolaboradorcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $colaborador = ColaboradorCargo::findOrFail($codcolaboradorcargo);
        $colaborador = ColaboradorCargoService::delete($colaborador);

        return response()->json([
            'result' => true
        ], 200);
    }

    public function comissaoCaixas(Request $request)
    {
        $inicio = $request->inicio;
        $fim = $request->fim;
        $ret = ColaboradorComissaoService::comissaoCaixas($inicio, $fim);
        return response()->json($ret, 200);
    }

}
