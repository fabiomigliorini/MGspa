<?php

namespace Mg\Colaborador;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class CargoController extends MgController
{

    public function index(Request $request)
    {
            $cargo = $request->cargo ?? null;
            $de = $request->de ?? null;
            $ate = $request->ate ?? null;

            $cargos = CargoService::index(
                $cargo??null,
                $de??null,
                $ate??null,
            );

              return new CargoResource($cargos);
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
        $cargo = Cargo::findOrFail($codcargo);
        $cargo = CargoService::update($cargo, $data);

        return new CargoResource($cargo);
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

    public function ativar($codcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $cargo = Cargo::findOrFail($codcargo);
        $cargo = CargoService::ativar($cargo);

        return new CargoResource($cargo);
    }

    public function inativar($codcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $cargo = Cargo::findOrFail($codcargo);
        $cargo = CargoService::inativar($cargo);

        return new CargoResource($cargo);
    }

    public function pessoasDoCargo($codcargo)
    {
        $pessoasCargo = CargoService::pessoasDoCargo($codcargo);
        $cargo = Cargo::findOrFail($codcargo);

        return  CargoResource::collection([
            'pessoasCargo' => $pessoasCargo,
            'cargoS' => $cargo
        ]);
    }

}
