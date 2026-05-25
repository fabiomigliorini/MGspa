<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\Banco\Banco;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class PessoaContaController extends MgController
{
    public function index(Request $request, $codpessoa)
    {
        $regs = PessoaConta::where('codpessoa', $codpessoa)->get();
        return PessoaContaResource::collection($regs);
    }

    public function create(Request $request)
    {
        Autorizador::autoriza(['Publico']);
        $reg = PessoaContaService::create($request->all());
        return new PessoaContaResource($reg);
    }

    public function show(Request $request, $codpessoa, $codpessoaconta)
    {
        $reg = PessoaConta::findOrFail($codpessoaconta);
        return new PessoaContaResource($reg);
    }

    public function update(Request $request, $codpessoa, $codpessoaconta)
    {
        Autorizador::autoriza(['Publico']);
        $reg = PessoaConta::findOrFail($codpessoaconta);
        $reg = PessoaContaService::update($reg, $request->all());
        return new PessoaContaResource($reg);
    }

    public function delete(Request $request, $codpessoa, $codpessoaconta)
    {
        Autorizador::autoriza(['Publico']);
        $reg = PessoaConta::findOrFail($codpessoaconta);
        $reg = PessoaContaService::delete($reg);
        return response()->json(['result' => $reg], 200);
    }

    public function selectBanco(Request $request)
    {
        if ($request->banco) {
            return response()->json(
                Banco::where('banco', 'ilike', "%{$request->banco}%")->get()
            );
        }
        if ($request->codbanco) {
            return response()->json(
                Banco::where('codbanco', $request->codbanco)->get()
            );
        }
        return response()->json('Nenhum resultado encontrado');
    }

    public function ativar($codpessoaconta)
    {
        Autorizador::autoriza(['Publico']);
        $reg = PessoaConta::findOrFail($codpessoaconta);
        $reg = PessoaContaService::ativar($reg);
        return new PessoaContaResource($reg);
    }

    public function inativar($codpessoaconta)
    {
        Autorizador::autoriza(['Publico']);
        $reg = PessoaConta::findOrFail($codpessoaconta);
        $reg = PessoaContaService::inativar($reg);
        return new PessoaContaResource($reg);
    }
}
