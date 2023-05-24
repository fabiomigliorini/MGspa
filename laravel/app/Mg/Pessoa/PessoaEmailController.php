<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PessoaEmailController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = PessoaEmail::orderBy('email', 'asc')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = PessoaEmailService::create($data);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codpessoatelefone)
    {
        $pessoa = PessoaEmail::findOrFail($codpessoatelefone);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codpessoatelefone)
    {
        $data = $request->all();
        $pessoa = PessoaEmail::findOrFail($codpessoatelefone);
        $pessoa = PessoaEmailService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codpessoatelefone)
    {

        $pessoa = PessoaEmail::findOrFail($codpessoatelefone);
        $pessoa = PessoaEmailService::delete($pessoa);
        return response()->json([
            'result' => $pessoa
        ], 200);

}
