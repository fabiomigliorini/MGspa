<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;

class EstadoCivilController extends MgController
{
    public function index(Request $request)
    {
        $estadocivil = $request->estadocivil ?? null;

        $estadosCivis = EstadoCivilService::index($estadocivil, $request->status ?? 'todos');

        return EstadoCivilResource::collection($estadosCivis);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $estadoCivil = EstadoCivilService::create($data);
        return new EstadoCivilResource($estadoCivil);
    }

    public function show($codestadocivil)
    {
        $estadoCivil = EstadoCivil::findOrFail($codestadocivil);
        return new EstadoCivilResource($estadoCivil);
    }

    public function update(Request $request, $codestadocivil)
    {
        $data = $request->all();
        $estadoCivil = EstadoCivil::findOrFail($codestadocivil);
        $estadoCivil = EstadoCivilService::update($estadoCivil, $data);
        return new EstadoCivilResource($estadoCivil);
    }

    public function destroy($codestadocivil)
    {
        $estadoCivil = EstadoCivil::findOrFail($codestadocivil);
        EstadoCivilService::delete($estadoCivil);
        return response()->json(['result' => true], 200);
    }

    public function inativar($codestadocivil)
    {
        $estadoCivil = EstadoCivil::findOrFail($codestadocivil);
        $estadoCivil = EstadoCivilService::inativar($estadoCivil);
        return new EstadoCivilResource($estadoCivil);
    }

    public function ativar($codestadocivil)
    {
        $estadoCivil = EstadoCivil::findOrFail($codestadocivil);
        $estadoCivil = EstadoCivilService::ativar($estadoCivil);
        return new EstadoCivilResource($estadoCivil);
    }
}
