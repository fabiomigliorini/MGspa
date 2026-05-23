<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;

class EtniaController extends MgController
{
    public function index(Request $request)
    {
        $regs = EtniaService::index($request->etnia ?? null, $request->status ?? 'todos');
        return EtniaResource::collection($regs);
    }

    public function store(Request $request)
    {
        $reg = EtniaService::create($request->all());
        return new EtniaResource($reg);
    }

    public function show($codetnia)
    {
        $reg = Etnia::findOrFail($codetnia);
        return new EtniaResource($reg);
    }

    public function update(Request $request, $codetnia)
    {
        $reg = Etnia::findOrFail($codetnia);
        $reg = EtniaService::update($reg, $request->all());
        return new EtniaResource($reg);
    }

    public function destroy($codetnia)
    {
        $reg = Etnia::findOrFail($codetnia);
        EtniaService::delete($reg);
        return response()->json(['result' => true], 200);
    }

    public function inativar($codetnia)
    {
        $reg = Etnia::findOrFail($codetnia);
        $reg = EtniaService::inativar($reg);
        return new EtniaResource($reg);
    }

    public function ativar($codetnia)
    {
        $reg = Etnia::findOrFail($codetnia);
        $reg = EtniaService::ativar($reg);
        return new EtniaResource($reg);
    }
}
