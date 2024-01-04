<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class GrupoClienteController extends MgController
{

    public function index(Request $request)
    {
        $grupos = GrupoCliente::orderBy('alteracao')->paginate();
        return response()->json($grupos, 200);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = GrupoClienteService::create($data);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codpessoatelefone)
    {
        $pessoa = GrupoCliente::findOrFail($codpessoatelefone);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codpessoatelefone)
    {
        $data = $request->all();
        $pessoa = GrupoCliente::findOrFail($codpessoatelefone);
        $pessoa = GrupoClienteService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codpessoatelefone)
    {

        $pessoa = GrupoCliente::findOrFail($codpessoatelefone);
        $pessoa = GrupoClienteService::delete($pessoa);
        return new PessoaResource($pessoa);
    }
   
}
