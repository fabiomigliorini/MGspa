<?php

namespace Mg\Cobranca;

use Illuminate\Http\Request;
use Mg\MgController;

class CobrancaHistoricoController extends MgController
{
    public function index($codpessoa)
    {
        $regs = CobrancaHistorico::where('codpessoa', $codpessoa)
            ->orderBy('criacao', 'desc')
            ->paginate(25);
        return CobrancaResource::collection($regs);
    }

    public function create(Request $request)
    {
        $reg = CobrancaHistoricoService::create($request->all());
        return new CobrancaResource($reg);
    }

    public function show(Request $request, $codpessoa, $codcobrancahistorico)
    {
        $reg = CobrancaHistorico::findOrFail($codcobrancahistorico);
        return new CobrancaResource($reg);
    }

    public function update(Request $request, $codpessoa, $codcobrancahistorico)
    {
        $reg = CobrancaHistorico::findOrFail($codcobrancahistorico);
        $reg = CobrancaHistoricoService::update($reg, $request->all());
        return new CobrancaResource($reg);
    }

    public function delete($codpessoa, $codcobrancahistorico)
    {
        $reg = CobrancaHistorico::findOrFail($codcobrancahistorico);
        $reg = CobrancaHistoricoService::delete($reg);
        return response()->json(['result' => $reg], 200);
    }
}
