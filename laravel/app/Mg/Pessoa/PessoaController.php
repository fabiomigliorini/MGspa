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
        $res = PessoaService::delete($pessoa);
        return response()->json([
            'result' => $res
        ], 200);
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

    public function importar (Request $request)
    {
        $request->validate([
            // 'cnpj' => 'required'
        ]);
        $cnpj = $request->cnpj??'';
        $codfilial = $request->codfilial;
        $cpf = $request->cpf??'';
        $ie = $request->ie??'';
        $uf = $request->uf??'';
        $pessoas = PessoaService::importar($codfilial, $uf, $cnpj, $cpf, $ie);
        return PessoaResource::collection($pessoas);
    }


    public function atualizaCampos($pessoa)
    {   
      $pessoa = PessoaService::atualizaCamposLegado($codpessoatelefone, $codpessoaemail, $codpessoaendereco);

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
