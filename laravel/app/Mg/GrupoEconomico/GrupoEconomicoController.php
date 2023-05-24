<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class GrupoEconomicoController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = GrupoEconomico::orderBy('alteracao')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = GrupoEconomicoService::create($data);
         dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codpessoaendereco)
    {
        $pessoa = GrupoEconomico::findOrFail($codpessoaendereco);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codpessoaendereco)
    {
        $data = $request->all();
        $pessoa = GrupoEconomico::findOrFail($codpessoaendereco);
        $pessoa = GrupoEconomicoService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codpessoaendereco)
    {

        $pessoa = GrupoEconomico::findOrFail($codpessoaendereco);
        $pessoa = GrupoEconomicoService::delete($pessoa);
        return new PessoaResource($pessoa);
    }

}
