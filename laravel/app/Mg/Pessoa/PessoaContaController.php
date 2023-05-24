<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PessoaContaController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = PessoaConta::orderBy('banco', 'asc')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = PessoaContaService::create($data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codpessoaconta)
    {
        $pessoa = PessoaConta::findOrFail($codpessoaconta);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codpessoaconta)
    {
        $data = $request->all();
        $pessoa = PessoaConta::findOrFail($codpessoaconta);
        $pessoa = PessoaContaService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codpessoaconta)
    {

        $pessoa = PessoaConta::findOrFail($codpessoaconta);
        $pessoa = PessoaContaService::delete($pessoa);
        return response()->json([
            'result' => $pessoa
        ], 200);
    }

}
