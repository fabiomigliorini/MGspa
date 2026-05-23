<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class PessoaEnderecoController extends MgController
{
    public function create(Request $request, $codpessoa)
    {
        Autorizador::autoriza(['Publico']);
        $request->validate([
            'codcidade' => ['required'],
            'endereco' => ['required'],
            'numero' => ['required'],
            'bairro' => ['required'],
            'cep' => ['required'],
        ]);

        $data = $request->all();
        $data['codpessoa'] = $codpessoa;
        $end = PessoaEnderecoService::create($data);
        return new PessoaResource($end->Pessoa);
    }

    public function show($codpessoa, $codpessoaendereco)
    {
        $end = PessoaEndereco::findOrFail($codpessoaendereco);
        return new PessoaEnderecoResource($end);
    }

    public function update(Request $request, $codpessoa, $codpessoaendereco)
    {
        Autorizador::autoriza(['Publico']);
        $end = PessoaEndereco::findOrFail($codpessoaendereco);
        $end = PessoaEnderecoService::update($end, $request->all());
        return new PessoaEnderecoResource($end);
    }

    public function delete(Request $request, $codpessoa, $codpessoaendereco)
    {
        Autorizador::autoriza(['Publico']);
        $end = PessoaEndereco::findOrFail($codpessoaendereco);
        $result = PessoaEnderecoService::delete($end);
        return response()->json(['result' => $result], 200);
    }

    public function cima(Request $request, $codpessoa, $codpessoaendereco)
    {
        $end = PessoaEndereco::findOrFail($codpessoaendereco);
        $end = PessoaEnderecoService::cima($end);
        $list = $end->Pessoa->PessoaEnderecoS()->orderBy('ordem', 'asc')->get();
        return PessoaEnderecoResource::collection($list);
    }

    public function baixo(Request $request, $codpessoa, $codpessoaendereco)
    {
        $end = PessoaEndereco::findOrFail($codpessoaendereco);
        $end = PessoaEnderecoService::baixo($end);
        $list = $end->Pessoa->PessoaEnderecoS()->orderBy('ordem', 'asc')->get();
        return PessoaEnderecoResource::collection($list);
    }

    public function ativar(Request $request, $codpessoa, $codpessoaendereco)
    {
        Autorizador::autoriza(['Publico']);
        $end = PessoaEndereco::findOrFail($codpessoaendereco);
        $end = PessoaEnderecoService::ativar($end);
        return new PessoaEnderecoResource($end);
    }

    public function inativar(Request $request, $codpessoa, $codpessoaendereco)
    {
        Autorizador::autoriza(['Publico']);
        $end = PessoaEndereco::findOrFail($codpessoaendereco);
        $end = PessoaEnderecoService::inativar($end);
        return new PessoaEnderecoResource($end);
    }
}
