<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PessoaEnderecoController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = PessoaEndereco::orderBy('endereco', 'asc')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = PessoaEnderecoService::create($data);
         dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codpessoaendereco)
    {
        $pessoa = PessoaEndereco::findOrFail($codpessoaendereco);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codpessoaendereco)
    {
        $data = $request->all();
        $pessoa = PessoaEndereco::findOrFail($codpessoaendereco);
        $pessoa = PessoaEnderecoService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codpessoaendereco)
    {

        $pessoa = PessoaEndereco::findOrFail($codpessoaendereco);
        $pessoa = PessoaEnderecoService::delete($pessoa);
        return new PessoaResource($pessoa);
    }

}
