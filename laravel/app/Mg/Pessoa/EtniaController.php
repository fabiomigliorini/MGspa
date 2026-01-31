<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;

class EtniaController extends MgController
{
    public function index(Request $request)
    {
        $etnia = $request->etnia ?? null;
        $inativo = $request->has('inativo') ? filter_var($request->inativo, FILTER_VALIDATE_BOOLEAN) : null;

        $etnias = EtniaService::index($etnia, $inativo);

        return EtniaResource::collection($etnias);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $etnia = EtniaService::create($data);
        return new EtniaResource($etnia);
    }

    public function show($codetnia)
    {
        $etnia = Etnia::findOrFail($codetnia);
        return new EtniaResource($etnia);
    }

    public function update(Request $request, $codetnia)
    {
        $data = $request->all();
        $etnia = Etnia::findOrFail($codetnia);
        $etnia = EtniaService::update($etnia, $data);
        return new EtniaResource($etnia);
    }

    public function destroy($codetnia)
    {
        $etnia = Etnia::findOrFail($codetnia);
        EtniaService::delete($etnia);
        return response()->json(['result' => true], 200);
    }

    public function inativar($codetnia)
    {
        $etnia = Etnia::findOrFail($codetnia);
        $etnia = EtniaService::inativar($etnia);
        return new EtniaResource($etnia);
    }

    public function ativar($codetnia)
    {
        $etnia = Etnia::findOrFail($codetnia);
        $etnia = EtniaService::ativar($etnia);
        return new EtniaResource($etnia);
    }
}
