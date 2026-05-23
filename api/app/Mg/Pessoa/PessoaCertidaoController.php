<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\Certidao\CertidaoEmissor;
use Mg\Certidao\CertidaoTipo;
use Mg\MgController;

class PessoaCertidaoController extends MgController
{
    public function create(Request $request)
    {
        $request->validate([
            'codpessoa' => 'required',
            'numero' => 'required',
            'validade' => 'required',
            'codcertidaotipo' => 'required',
            'codcertidaoemissor' => 'required',
        ]);

        $reg = PessoaCertidaoService::create($request->all());
        return new PessoaCertidaoResource($reg);
    }

    public function show($codpessoacertidao)
    {
        $reg = PessoaCertidao::findOrFail($codpessoacertidao);
        return new PessoaCertidaoResource($reg);
    }

    public function update(Request $request, $codpessoacertidao)
    {
        $reg = PessoaCertidao::findOrFail($codpessoacertidao);
        $reg = PessoaCertidaoService::update($reg, $request->all());
        return new PessoaCertidaoResource($reg);
    }

    public function delete($codpessoacertidao)
    {
        $reg = PessoaCertidao::findOrFail($codpessoacertidao);
        $reg = PessoaCertidaoService::delete($reg);
        return response()->json(['result' => $reg], 200);
    }

    public function selectCertidaoEmissor()
    {
        $regs = CertidaoEmissor::orderBy('certidaoemissor', 'desc')->get();
        return response()->json($regs, 200);
    }

    public function selectCertidaoTipo()
    {
        $regs = CertidaoTipo::orderBy('certidaotipo', 'desc')->get();
        return response()->json($regs, 200);
    }

    public function ativar($codpessoacertidao)
    {
        $reg = PessoaCertidao::findOrFail($codpessoacertidao);
        $reg = PessoaCertidaoService::ativar($reg);
        return new PessoaCertidaoResource($reg);
    }

    public function inativar($codpessoacertidao)
    {
        $reg = PessoaCertidao::findOrFail($codpessoacertidao);
        $reg = PessoaCertidaoService::inativar($reg);
        return new PessoaCertidaoResource($reg);
    }
}
