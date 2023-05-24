<?php

namespace Mg\Certidao;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Mg\Pessoa\PessoaResource;

class CertidaoTipoController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = CertidaoTipo::orderBy('sigla', 'asc')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = CertidaoTipoService::create($data);
         dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codcertidaotipo)
    {
        $pessoa = CertidaoTipo::findOrFail($codcertidaotipo);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codcertidaotipo)
    {
        $data = $request->all();
        $pessoa = CertidaoTipo::findOrFail($codcertidaotipo);
        $pessoa = CertidaoTipoService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codcertidaotipo)
    {

        $pessoa = CertidaoTipo::findOrFail($codcertidaotipo);
        $pessoa = CertidaoTipoService::delete($pessoa);
        return new PessoaResource($pessoa);
    }

}
