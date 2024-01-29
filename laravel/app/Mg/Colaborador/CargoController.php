<?php

namespace Mg\Colaborador;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class CargoController extends MgController
{

    public function index()
    {
            $cargos = Cargo::orderBy('cargo', 'asc')->get();
              return response()->json($cargos, 200);
    }

    public function create(Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $data = $request->all();
        $criarCargo = Cargo::create($data);
        return new CargoResource($criarCargo);
    }

    public function show(Request $request, $codcargo)
    {
        $cargo = Cargo::findOrFail($codcargo);
        return new CargoResource($cargo);
    }

    public function update(Request $request, $codcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $data = $request->all();
        $ferias = Cargo::findOrFail($codcargo);
        $ferias = CargoService::update($codcargo, $data);

        return new CargoResource($ferias);
    }

    public function delete(Request $request, $codcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $ferias = Cargo::findOrFail($codcargo);
        $ferias = CargoService::delete($ferias);

        return response()->json([
            'result' => true
        ], 200);
    }
}
