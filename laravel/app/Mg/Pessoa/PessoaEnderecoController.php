<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Pessoa\PessoaEndereco;

class PessoaEnderecoController extends MgController
{

    public function index(Request $request, $codpessoa)
    {
        $pes = PessoaEndereco::where('codpessoa', $codpessoa)->orderBy('ordem', 'asc')->get();
        return PessoaEnderecoResource::collection($pes);
    }

    public function create(Request $request, $codpessoa)
    {
        $request->validate([
            'codcidade' => ['required'],
            'endereco' => ['required'],
            'numero' => ['required'],
            'bairro' => ['required'],
            'cep' => ['required'],
        ]);
        $data = $request->all();
        $data['codpessoa'] = $codpessoa;
        $pe = PessoaEnderecoService::create($data);
        $pes = $pe->Pessoa->PessoaEnderecoS()->orderBy('ordem', 'asc')->get();
        return PessoaEnderecoResource::collection($pes);
    }

    public function show($codpessoaendereco)
    {
        $pe = PessoaEndereco::findOrFail($codpessoaendereco);
        return PessoaEnderecoResource::collection($pe);
    }

    public function update(Request $request, $codpessoa, $codpessoaendereco)
    {
        $data = $request->all();
        $pessoa = PessoaEndereco::findOrFail($codpessoaendereco);
        $pessoa = PessoaEnderecoService::update($pessoa, $data);
        return new PessoaEnderecoResource($pessoa);
    }

    public function delete(Request $request, $codpessoa, $codpessoaendereco)
    {

        $pessoa = PessoaEndereco::findOrFail($codpessoaendereco);
        $pessoa = PessoaEnderecoService::delete($pessoa);
        return response()->json([
            'result' => $pessoa
        ], 200);
    }


    public function cima(Request $request, $codpessoa, $codpessoaendereco)
    {
        $pe = PessoaEndereco::findOrFail($codpessoaendereco);
        $pe = PessoaEnderecoService::cima($pe);
        $pes = $pe->Pessoa->PessoaEnderecoS()->orderBy('ordem', 'asc')->get();
        return PessoaEnderecoResource::collection($pes);
    }

    public function baixo(Request $request, $codpessoa, $codpessoaendereco)
    {
        $pe = PessoaEndereco::findOrFail($codpessoaendereco);
        $pe = PessoaEnderecoService::baixo($pe);
        $pes = $pe->Pessoa->PessoaEnderecoS()->orderBy('ordem', 'asc')->get();
        return PessoaEnderecoResource::collection($pes);
    }

    public function ativar(Request $request, $codpessoa, $codpessoaendereco) {
        $pes = PessoaEndereco::findOrFail($codpessoaendereco);
        $pes = PessoaEnderecoService::ativar($pes);

        return new PessoaEnderecoResource($pes);
    }

    public function inativar(Request $request, $codpessoa, $codpessoaendereco) {
        $pes = PessoaEndereco::findOrFail($codpessoaendereco);
        $pes = PessoaEnderecoService::inativar($pes);

        return new PessoaEnderecoResource($pes);
    }
}
