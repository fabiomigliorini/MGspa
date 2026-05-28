<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;

class GrauInstrucaoController extends MgController
{
    public function index(Request $request)
    {
        $regs = GrauInstrucaoService::index($request->grauinstrucao ?? null, $request->status ?? 'todos');
        return GrauInstrucaoResource::collection($regs);
    }

    public function store(Request $request)
    {
        $reg = GrauInstrucaoService::create($request->all());
        return new GrauInstrucaoResource($reg);
    }

    public function show($codgrauinstrucao)
    {
        $reg = GrauInstrucao::findOrFail($codgrauinstrucao);
        return new GrauInstrucaoResource($reg);
    }

    public function update(Request $request, $codgrauinstrucao)
    {
        $reg = GrauInstrucao::findOrFail($codgrauinstrucao);
        $reg = GrauInstrucaoService::update($reg, $request->all());
        return new GrauInstrucaoResource($reg);
    }

    public function destroy($codgrauinstrucao)
    {
        $reg = GrauInstrucao::findOrFail($codgrauinstrucao);
        GrauInstrucaoService::delete($reg);
        return response()->json(['result' => true], 200);
    }

    public function inativar($codgrauinstrucao)
    {
        $reg = GrauInstrucao::findOrFail($codgrauinstrucao);
        $reg = GrauInstrucaoService::inativar($reg);
        return new GrauInstrucaoResource($reg);
    }

    public function ativar($codgrauinstrucao)
    {
        $reg = GrauInstrucao::findOrFail($codgrauinstrucao);
        $reg = GrauInstrucaoService::ativar($reg);
        return new GrauInstrucaoResource($reg);
    }
}
