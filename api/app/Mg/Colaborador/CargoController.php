<?php

namespace Mg\Colaborador;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class CargoController extends MgController
{
    public function index(Request $request)
    {
        $cargos = CargoService::index(
            $request->cargo ?? null,
            $request->de ?? null,
            $request->ate ?? null,
        );
        return new CargoResource($cargos);
    }

    public function create(Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $reg = Cargo::create($request->all());
        return new CargoResource($reg);
    }

    public function show(Request $request, $codcargo)
    {
        $reg = Cargo::findOrFail($codcargo);
        return new CargoResource($reg);
    }

    public function update(Request $request, $codcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $reg = Cargo::findOrFail($codcargo);
        $reg = CargoService::update($reg, $request->all());
        return new CargoResource($reg);
    }

    public function delete(Request $request, $codcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $reg = Cargo::findOrFail($codcargo);
        CargoService::delete($reg);
        return response()->json(['result' => true], 200);
    }

    public function ativar($codcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $reg = Cargo::findOrFail($codcargo);
        $reg = CargoService::ativar($reg);
        return new CargoResource($reg);
    }

    public function inativar($codcargo)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $reg = Cargo::findOrFail($codcargo);
        $reg = CargoService::inativar($reg);
        return new CargoResource($reg);
    }

    public function pessoasDoCargo($codcargo)
    {
        $pessoasCargo = CargoService::pessoasDoCargo($codcargo);
        $cargo = Cargo::findOrFail($codcargo);

        return CargoResource::collection([
            'pessoasCargo' => $pessoasCargo,
            'cargoS' => $cargo,
        ]);
    }
}
