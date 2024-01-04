<?php

namespace Mg\Pessoa;

use Exception;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;
use Mg\Pessoa\PessoaEndereco;

class PessoaEnderecoController extends MgController
{


    // TODO: fazer o retornar sempre o resource de Pessoa e nao Pessoa Endereco
    public function create(Request $request, $codpessoa)
    {
        Autorizador::autoriza(array('Financeiro'));

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
        return new PessoaResource($pe->Pessoa);
        // return new PessoaEnderecoResource($pe);
    }

    public function show($codpessoaendereco)
    {
        $pe = PessoaEndereco::findOrFail($codpessoaendereco);
        return PessoaEnderecoResource::collection($pe);
    }

    public function update(Request $request, $codpessoa, $codpessoaendereco)
    {
        Autorizador::autoriza(array('Financeiro'));

        $data = $request->all();
        $pessoa = PessoaEndereco::findOrFail($codpessoaendereco);
        $pessoa = PessoaEnderecoService::update($pessoa, $data);
        return new PessoaEnderecoResource($pessoa);
    }

    public function delete(Request $request, $codpessoa, $codpessoaendereco)
    {   
        Autorizador::autoriza(array('Financeiro'));

        $pe = PessoaEndereco::findOrFail($codpessoaendereco);
        $result = PessoaEnderecoService::delete($pe);
        return response()->json([
            'result' => $result
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

    public function ativar(Request $request, $codpessoa, $codpessoaendereco)
    {   
        Autorizador::autoriza(array('Financeiro'));

        $pes = PessoaEndereco::findOrFail($codpessoaendereco);
        $pes = PessoaEnderecoService::ativar($pes);

        return new PessoaEnderecoResource($pes);
    }

    public function inativar(Request $request, $codpessoa, $codpessoaendereco)
    {   
         Autorizador::autoriza(array('Financeiro'));
        
        $pes = PessoaEndereco::findOrFail($codpessoaendereco);
        $pes = PessoaEnderecoService::inativar($pes);

        return new PessoaEnderecoResource($pes);
    }
}
