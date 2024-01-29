<?php

namespace Mg\Colaborador;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ColaboradorCargoController extends MgController
{

    public function create(Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $data = $request->all();
        $criarcolaborador = ColaboradorCargoService::create($data);
        return new ColaboradorCargoResource($criarcolaborador);
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
}
