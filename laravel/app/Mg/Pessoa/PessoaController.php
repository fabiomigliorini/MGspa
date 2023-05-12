<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PessoaController extends MgController
{

    public function index(Request $request)
    {
        $pessoas = Pessoa::orderBy('fantasia', 'asc')->paginate();
        return PessoaResource::collection($pessoas);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $pessoa = PessoaService::create($data);
        return new PessoaResource($pessoa);
    }

    public function show (Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        return new PessoaResource($pessoa);
    }

    public function update (Request $request, $codpessoa)
    {
        $data = $request->all();
        $pessoa = Pessoa::findOrFail($codpessoa);
        $pessoa = PessoaService::update($pessoa, $data);
        return new PessoaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        $pessoa = PessoaService::delete($pessoa);
        return new PessoaResource($pessoa);
    }

    public function ativar (Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        $pessoa = PessoaService::ativar($pessoa);
        return new PessoaResource($pessoa);
    }

    public function inativar (Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        $pessoa = PessoaService::inativar($pessoa);
        return new PessoaResource($pessoa);
    }

    public function importarReceitaWs (Request $request)
    {
        $request->validate([
            'cnpj' => 'required'
        ]);
        $cnpj = $request->cnpj;
        $pessoa = PessoaService::importarReceitaWs($cnpj);
        return new PessoaResource($pessoa);
    }

    public function importarSefaz (Request $request)
    {
        $request->validate([
            'uf' => 'required'
            // 'cnpj' => 'required'
        ]);
        $uf = $request->uf;
        $cnpj = $request->cnpj??'';
        $cpf = $request->cpf??'';
        $ie = $request->ie??'';
        $pessoa = PessoaService::importarSefaz($uf, $cnpj, $cpf, $ie);
        return new PessoaResource($pessoa);
    }

    public function comandaVendedor (Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        if (!$pessoa->vendedor) {
            throw new \Exception("\"{$pessoa->fantasia}\" não é vendedor!", 1);
        }
        $pdf = PessoaComandaVendedorService::pdf($pessoa);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda'.$codpessoa.'.pdf"'
        ]);
    }

    public function comandaVendedorImprimir (Request $request, $codpessoa)
    {
        $request->validate([
            'impressora' => ['required', 'string'],
            'copias' => ['required', 'integer']
        ]);
        $pessoa = Pessoa::findOrFail($codpessoa);
        if (!$pessoa->vendedor) {
            throw new \Exception("\"{$pessoa->fantasia}\" não é vendedor!", 1);
        }
        $pdf = PessoaComandaVendedorService::imprimir($pessoa, $request->impressora, $request->copias);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda'.$codpessoa.'.pdf"'
        ]);
    }

    public function autocomplete (Request $request)
    {
        $qry = PessoaService::autocomplete($request->all());
        return response()->json($qry, 200);
    }

}
