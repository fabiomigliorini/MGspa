<?php

namespace Mg\Cobranca;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Mg\Pessoa\PessoaResource;

class CobrancaHistoricoController extends MgController
{

    public function index($codpessoa)
    {
        $CobrancaHistorico = CobrancaHistorico::where('codpessoa', $codpessoa)
            ->orderBy('criacao', 'desc')->paginate(25);

        return CobrancaResource::collection($CobrancaHistorico);
    }

    public function create(Request $request)
    {
        $data = $request->all();
        $cobranca = CobrancaHistoricoService::create($data);
        return new CobrancaResource($cobranca);
    }

    public function show(Request $request, $codpessoa, $codcobrancahistorico)
    {
        $pessoa = CobrancaHistorico::findOrFail($codcobrancahistorico);
        return new PessoaResource($pessoa);
    }

    public function update(Request $request, $codpessoa, $codcobrancahistorico)
    {
        $data = $request->all();
        $pessoa = CobrancaHistorico::findOrFail($codcobrancahistorico);
        $pessoa = CobrancaHistoricoService::update($pessoa, $data);
        
        return new CobrancaResource($pessoa);
    }

    public function delete($codpessoa, $codcobrancahistorico)
    {   
        $historico = CobrancaHistorico::findOrFail($codcobrancahistorico);
        $historico = CobrancaHistoricoService::delete($historico);
        return response()->json([
            'result' => $historico
        ], 200);
    }
    
}
