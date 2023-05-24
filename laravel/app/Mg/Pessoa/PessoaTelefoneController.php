<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PessoaTelefoneController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = PessoaTelefone::orderBy('telefone', 'asc')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = PessoaTelefoneService::create($data);
         dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codpessoatelefone)
    {
        $pessoa = PessoaTelefone::findOrFail($codpessoatelefone);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codpessoatelefone)
    {
        $data = $request->all();
        $pessoa = PessoaTelefone::findOrFail($codpessoatelefone);
        $pessoa = PessoaTelefoneService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codpessoatelefone)
    {

        $pessoa = PessoaTelefone::findOrFail($codpessoatelefone);
        $pessoa = PessoaTelefoneService::delete($pessoa);
        return response()->json([
            'result' => $pessoa
        ], 200);
    }

}
