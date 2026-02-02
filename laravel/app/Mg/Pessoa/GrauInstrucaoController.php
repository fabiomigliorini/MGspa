<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;

class GrauInstrucaoController extends MgController
{
    public function index(Request $request)
    {
        $grauinstrucao = $request->grauinstrucao ?? null;

        $grausInstrucao = GrauInstrucaoService::index($grauinstrucao, $request->status ?? 'todos');

        return GrauInstrucaoResource::collection($grausInstrucao);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $grauInstrucao = GrauInstrucaoService::create($data);
        return new GrauInstrucaoResource($grauInstrucao);
    }

    public function show($codgrauinstrucao)
    {
        $grauInstrucao = GrauInstrucao::findOrFail($codgrauinstrucao);
        return new GrauInstrucaoResource($grauInstrucao);
    }

    public function update(Request $request, $codgrauinstrucao)
    {
        $data = $request->all();
        $grauInstrucao = GrauInstrucao::findOrFail($codgrauinstrucao);
        $grauInstrucao = GrauInstrucaoService::update($grauInstrucao, $data);
        return new GrauInstrucaoResource($grauInstrucao);
    }

    public function destroy($codgrauinstrucao)
    {
        $grauInstrucao = GrauInstrucao::findOrFail($codgrauinstrucao);
        GrauInstrucaoService::delete($grauInstrucao);
        return response()->json(['result' => true], 200);
    }

    public function inativar($codgrauinstrucao)
    {
        $grauInstrucao = GrauInstrucao::findOrFail($codgrauinstrucao);
        $grauInstrucao = GrauInstrucaoService::inativar($grauInstrucao);
        return new GrauInstrucaoResource($grauInstrucao);
    }

    public function ativar($codgrauinstrucao)
    {
        $grauInstrucao = GrauInstrucao::findOrFail($codgrauinstrucao);
        $grauInstrucao = GrauInstrucaoService::ativar($grauInstrucao);
        return new GrauInstrucaoResource($grauInstrucao);
    }
}
