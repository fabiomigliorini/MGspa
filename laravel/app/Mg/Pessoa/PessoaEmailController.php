<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PessoaEmailController extends MgController
{

    public function index(Request $request, $codpessoa)
    {
        $pessoas = PessoaEmail::where('codpessoa', $codpessoa)->orderBy('ordem', 'asc')->get();
        return PessoaEmailResource::collection($pessoas);
    }

    public function create(Request $request, $codpessoa)
    {   
        $request->validate([
            'email' => ['required'],
        ]);

        $data = $request->all();
        $data['codpessoa'] = $codpessoa;
        $pemail = PessoaEmailService::create($data);
        $pemail = $pemail->Pessoa->PessoaEmailS()->orderBy('ordem', 'asc')->get();
        return PessoaEmailResource::collection($pemail);
    }

    public function show(Request $request, $codpessoa, $codpessoatelefone)
    {
        $pessoa = PessoaEmail::findOrFail($codpessoatelefone);
        return PessoaEmailResource::collection($pessoa);
    }

    public function update(Request $request, $codpessoa, $codpessoatelefone)
    {
        $data = $request->all();
        $pessoa = PessoaEmail::findOrFail($codpessoatelefone);
        $pessoa = PessoaEmailService::update($pessoa, $data);
        return new PessoaEmailResource($pessoa);       
    }

    public function delete(Request $request, $codpessoa, $codpessoatelefone)
    {

        $pessoa = PessoaEmail::findOrFail($codpessoatelefone);
        $pessoa = PessoaEmailService::delete($pessoa);
        return response()->json([
            'result' => $pessoa
        ], 200);
    }

    public function cima(Request $request, $codpessoa, $codpessoatelefone)
    {
        $ptt = PessoaEmail::findOrFail($codpessoatelefone);
        $ptt = PessoaEmailService::cima($ptt);
        $pes = $ptt->Pessoa->PessoaEmailS()->orderBy('ordem', 'asc')->get();
        return PessoaEmailResource::collection($pes);
    }

    public function baixo(Request $request, $codpessoa, $codpessoatelefone)
    {
        $ptt = PessoaEmail::findOrFail($codpessoatelefone);
        $ptt = PessoaEmailService::baixo($ptt);
        $pes = $ptt->Pessoa->PessoaEmailS()->orderBy('ordem', 'asc')->get();
        return PessoaEmailResource::collection($pes);
    }

    public function ativar(Request $request, $codpessoa, $codpessoatelefone) {
        $pes = PessoaEmail::findOrFail($codpessoatelefone);
        $pes = PessoaEmailService::ativar($pes);

        return new PessoaEmailResource($pes);
    }

    public function inativar(Request $request, $codpessoa, $codpessoatelefone) {
        $pes = PessoaEmail::findOrFail($codpessoatelefone);
        $pes = PessoaEmailService::inativar($pes);

        return new PessoaEmailResource($pes);
    }
}
