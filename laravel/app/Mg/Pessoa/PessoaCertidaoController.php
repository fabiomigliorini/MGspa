<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Mg\Certidao\CertidaoEmissor;
use Mg\Certidao\CertidaoTipo;

class PessoaCertidaoController extends MgController
{

    public function create(Request $request)
    {
        $data = $request->all();

        $this->validate($request, [
            'codpessoa' => 'required',
            'numero' => 'required',
            'validade' => 'required',
            'codcertidaotipo' => 'required',
            'codcertidaoemissor' => 'required',

        ]);

        $pessoaCertidao = PessoaCertidaoService::create($data);
        return new PessoaCertidaoResource($pessoaCertidao);
    }

    public function show($codpessoacertidao)
    {
        $pessoaCertidao = PessoaCertidao::findOrFail($codpessoacertidao);
        return new PessoaCertidaoResource($pessoaCertidao);
    }

    public function update(Request $request, $codpessoacertidao)
    {
        $data = $request->all();
        $pessoaCertidao = PessoaCertidao::findOrFail($codpessoacertidao);
        $pessoaCertidao = PessoaCertidaoService::update($pessoaCertidao, $data);
        return new PessoaCertidaoResource($pessoaCertidao);
    }

    public function delete($codpessoacertidao)
    {

        $pessoaCertidao = PessoaCertidao::findOrFail($codpessoacertidao);
        $pessoaCertidao = PessoaCertidaoService::delete($pessoaCertidao);
        return response()->json([
            'result' => $pessoaCertidao
        ], 200);
    }


    public function selectCertidaoEmissor()
    {
       $certidaoEmissor =  CertidaoEmissor::orderBy('certidaoemissor', 'desc')->get();

       return response()->json($certidaoEmissor, 200);
    }

    public function selectCertidaoTipo()
    {
        $certidaoTipo = CertidaoTipo::orderBy('certidaotipo', 'desc')->get();
        return response()->json($certidaoTipo, 200);
    }

    public function ativar($codpessoacertidao)
    {
        $certidao = PessoaCertidao::findOrFail($codpessoacertidao);
        $certidao = PessoaCertidaoService::ativar($certidao);

        return new PessoaCertidaoResource($certidao);
    }

    public function inativar($codpessoacertidao)
    {
        $certidao = PessoaCertidao::findOrFail($codpessoacertidao);
        $certidao = PessoaCertidaoService::inativar($certidao);

        return new PessoaCertidaoResource($certidao);
    }
}
