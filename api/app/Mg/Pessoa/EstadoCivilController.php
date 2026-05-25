<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;

class EstadoCivilController extends MgController
{
    public function index(Request $request)
    {
        $regs = EstadoCivilService::index($request->estadocivil ?? null, $request->status ?? 'todos');
        return EstadoCivilResource::collection($regs);
    }

    public function store(Request $request)
    {
        $reg = EstadoCivilService::create($request->all());
        return new EstadoCivilResource($reg);
    }

    public function show($codestadocivil)
    {
        $reg = EstadoCivil::findOrFail($codestadocivil);
        return new EstadoCivilResource($reg);
    }

    public function update(Request $request, $codestadocivil)
    {
        $reg = EstadoCivil::findOrFail($codestadocivil);
        $reg = EstadoCivilService::update($reg, $request->all());
        return new EstadoCivilResource($reg);
    }

    public function destroy($codestadocivil)
    {
        $reg = EstadoCivil::findOrFail($codestadocivil);
        EstadoCivilService::delete($reg);
        return response()->json(['result' => true], 200);
    }

    public function inativar($codestadocivil)
    {
        $reg = EstadoCivil::findOrFail($codestadocivil);
        $reg = EstadoCivilService::inativar($reg);
        return new EstadoCivilResource($reg);
    }

    public function ativar($codestadocivil)
    {
        $reg = EstadoCivil::findOrFail($codestadocivil);
        $reg = EstadoCivilService::ativar($reg);
        return new EstadoCivilResource($reg);
    }
}
