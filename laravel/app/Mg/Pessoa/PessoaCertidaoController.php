<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PessoaCertidaoController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = PessoaCertidao::orderBy('numero', 'asc')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = PessoaCertidaoService::create($data);
         dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codpessoacertidao)
    {
        $pessoa = PessoaCertidao::findOrFail($codpessoacertidao);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codpessoacertidao)
    {
        $data = $request->all();
        $pessoa = PessoaCertidao::findOrFail($codpessoacertidao);
        $pessoa = PessoaCertidaoService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codpessoacertidao)
    {

        $pessoa = PessoaCertidao::findOrFail($codpessoacertidao);
        $pessoa = PessoaCertidaoService::delete($pessoa);
        return new PessoaResource($pessoa);
    }

}
