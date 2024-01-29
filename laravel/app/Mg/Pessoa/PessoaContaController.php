<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Mg\Banco\Banco;
use Mg\Usuario\Autorizador;

class PessoaContaController extends MgController
{

    public function index(Request $request, $codpessoa)
    {
        $pessoaConta = PessoaConta::where('codpessoa', $codpessoa)->get();
        return PessoaContaResource::collection($pessoaConta);
    }

    public function create (Request $request)
    {

        Autorizador::autoriza(['Financeiro']);
        $data = $request->all();
        $pessoa = PessoaContaService::create($data);
        return new PessoaContaResource($pessoa);
    }

    public function show (Request $request, $codpessoa, $codpessoaconta)
    {
        $pessoa = PessoaConta::findOrFail($codpessoaconta);
        return new PessoaContaResource($pessoa);
    }

    public function update (Request $request, $codpessoa, $codpessoaconta)
    {
        Autorizador::autoriza(['Financeiro']);
        $data = $request->all();
        $pessoa = PessoaConta::findOrFail($codpessoaconta);
        $pessoa = PessoaContaService::update($pessoa, $data);
        return new PessoaContaResource($pessoa);
    }

    public function delete (Request $request, $codpessoa, $codpessoaconta)
    {

        Autorizador::autoriza(['Financeiro']);
        $pessoa = PessoaConta::findOrFail($codpessoaconta);
        $pessoa = PessoaContaService::delete($pessoa);
        return response()->json([
            'result' => $pessoa
        ], 200);
    }


    public function selectBanco(Request $request)
    {
        if ($request->banco) {
            $search = Banco::where('banco', 'ilike', "%$request->banco%")->get();
            return response()->json($search);
        }

        if($request->codbanco) {
            $search = Banco::where('codbanco', $request->codbanco)->get();
            return response()->json($search);
        }
       
        return response()->json('Nenhum resultado encontrado');
    }


    public function ativar($codpessoaconta)
    {
        Autorizador::autoriza(['Financeiro']);
        $pessaoConta = PessoaConta::findOrFail($codpessoaconta);
        $pessaoConta = PessoaContaService::ativar($pessaoConta);

        return new PessoaContaResource($pessaoConta);
    }

    public function inativar($codpessoaconta)
    {
        Autorizador::autoriza(['Financeiro']);
        $pessaoConta = PessoaConta::findOrFail($codpessoaconta);
        $pessaoConta = PessoaContaService::inativar($pessaoConta);

        return new PessoaContaResource($pessaoConta);
    }


}
