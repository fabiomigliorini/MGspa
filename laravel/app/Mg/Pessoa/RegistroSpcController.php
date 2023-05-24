<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class RegistroSpcController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = RegistroSpc::orderBy('inclusao', 'asc')->paginate();
        dd($pessoas);
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = RegistroSpcService::create($data);
         dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codregistrospc)
    {
        $pessoa = RegistroSpc::findOrFail($codregistrospc);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codregistrospc)
    {
        $data = $request->all();
        $pessoa = RegistroSpc::findOrFail($codregistrospc);
        $pessoa = RegistroSpcService::update($pessoa, $data);
        dd($pessoa);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codregistrospc)
    {

        $pessoa = RegistroSpc::findOrFail($codregistrospc);
        $pessoa = RegistroSpcService::delete($pessoa);
        return new PessoaResource($pessoa);
    }

}
