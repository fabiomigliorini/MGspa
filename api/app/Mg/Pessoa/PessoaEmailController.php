<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class PessoaEmailController extends MgController
{
    public function index(Request $request, $codpessoa)
    {
        $items = PessoaEmail::where('codpessoa', $codpessoa)->orderBy('ordem', 'asc')->get();
        return PessoaEmailResource::collection($items);
    }

    public function create(Request $request, $codpessoa)
    {
        Autorizador::autoriza(['Publico']);
        $request->validate(['email' => ['required']]);

        $data = $request->all();
        $data['codpessoa'] = $codpessoa;
        $email = PessoaEmailService::create($data);
        $list = $email->Pessoa->PessoaEmailS()->orderBy('ordem', 'asc')->get();
        return PessoaEmailResource::collection($list);
    }

    public function show(Request $request, $codpessoa, $codpessoaemail)
    {
        $email = PessoaEmail::findOrFail($codpessoaemail);
        return new PessoaEmailResource($email);
    }

    public function update(Request $request, $codpessoa, $codpessoaemail)
    {
        Autorizador::autoriza(['Publico']);
        $email = PessoaEmail::findOrFail($codpessoaemail);
        $email = PessoaEmailService::update($email, $request->all());
        return new PessoaEmailResource($email);
    }

    public function delete(Request $request, $codpessoa, $codpessoaemail)
    {
        Autorizador::autoriza(['Publico']);
        $email = PessoaEmail::findOrFail($codpessoaemail);
        PessoaEmailService::delete($email);
        return response()->json(['result' => $email], 200);
    }

    public function cima(Request $request, $codpessoa, $codpessoaemail)
    {
        $email = PessoaEmail::findOrFail($codpessoaemail);
        $email = PessoaEmailService::cima($email);
        $list = $email->Pessoa->PessoaEmailS()->orderBy('ordem', 'asc')->get();
        return PessoaEmailResource::collection($list);
    }

    public function baixo(Request $request, $codpessoa, $codpessoaemail)
    {
        $email = PessoaEmail::findOrFail($codpessoaemail);
        $email = PessoaEmailService::baixo($email);
        $list = $email->Pessoa->PessoaEmailS()->orderBy('ordem', 'asc')->get();
        return PessoaEmailResource::collection($list);
    }

    public function ativar(Request $request, $codpessoa, $codpessoaemail)
    {
        Autorizador::autoriza(['Publico']);
        $email = PessoaEmail::findOrFail($codpessoaemail);
        $email = PessoaEmailService::ativar($email);
        return new PessoaEmailResource($email);
    }

    public function inativar(Request $request, $codpessoa, $codpessoaemail)
    {
        Autorizador::autoriza(['Publico']);
        $email = PessoaEmail::findOrFail($codpessoaemail);
        $email = PessoaEmailService::inativar($email);
        return new PessoaEmailResource($email);
    }

    public function verificarEmail($codpessoa, $codpessoaemail)
    {
        $email = PessoaEmail::findOrFail($codpessoaemail);
        return PessoaEmailService::verificaEmail($email);
    }

    public function confirmaEmail(Request $request, $codpessoa, $codpessoaemail)
    {
        $request->validate(['codverificacao' => ['required']]);
        $email = PessoaEmail::findOrFail($codpessoaemail);
        $email = PessoaEmailService::confirmaVerificacao($email, $request->codverificacao);
        return new PessoaEmailResource($email);
    }
}
