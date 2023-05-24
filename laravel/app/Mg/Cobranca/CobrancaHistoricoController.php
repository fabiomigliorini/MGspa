<?php

namespace Mg\Cobranca;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Mg\Pessoa\PessoaResource;

class CobrancaHistoricoController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = CobrancaHistorico::orderBy('historico', 'asc')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = CobrancaHistoricoService::create($data);
         dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codcobrancahistorico)
    {
        $pessoa = CobrancaHistorico::findOrFail($codcobrancahistorico);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codcobrancahistorico)
    {
        $data = $request->all();
        $pessoa = CobrancaHistorico::findOrFail($codcobrancahistorico);
        $pessoa = CobrancaHistoricoService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codcobrancahistorico)
    {

        $pessoa = CobrancaHistorico::findOrFail($codcobrancahistorico);
        $pessoa = CobrancaHistoricoService::delete($pessoa);
        return new PessoaResource($pessoa);
    }

}
