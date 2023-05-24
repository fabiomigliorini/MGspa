<?php

namespace Mg\Certidao;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Mg\Pessoa\PessoaResource;

class CertidaoEmissorController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = CertidaoEmissor::orderBy('certidaoemissor', 'asc')->paginate();
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = CertidaoEmissorService::create($data);
         dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codcertidaoemissor)
    {
        $pessoa = CertidaoEmissor::findOrFail($codcertidaoemissor);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codcertidaoemissor)
    {
        $data = $request->all();
        $pessoa = CertidaoEmissor::findOrFail($codcertidaoemissor);
        $pessoa = CertidaoEmissorService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codcertidaoemissor)
    {

        $pessoa = CertidaoEmissor::findOrFail($codcertidaoemissor);
        $pessoa = CertidaoEmissorService::delete($pessoa);
        return new PessoaResource($pessoa);
    }

}
