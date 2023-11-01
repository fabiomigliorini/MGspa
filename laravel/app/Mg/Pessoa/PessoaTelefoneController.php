<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Mg\MgController;

class PessoaTelefoneController extends MgController
{

    public function index(Request $request, $codpessoa)
    {
        $pessoas = PessoaTelefone::where('codpessoa', $codpessoa)->orderBy('ordem', 'asc')->get();

        return PessoaTelefoneResource::collection($pessoas);
    }

    public function create(Request $request, $codpessoa)
    {

        $request->validate([
            'tipo' => ['required'],
            'pais' => ['required'],
            'ddd' => ['required'],
            'telefone' => ['required'],
        ]);


        $data = $request->all();
        $data['codpessoa'] = $codpessoa;
        $pt = PessoaTelefoneService::create($data);
        $pts = $pt->Pessoa->PessoaTelefoneS()->orderBy('ordem', 'asc')->get();
        return PessoaTelefoneResource::collection($pts);
    }

    public function show(Request $request, $codpessoa, $codpessoatelefone)
    {
        $pessoa = PessoaTelefone::findOrFail($codpessoatelefone);
        return PessoaTelefoneResource::collection($pessoa);
    }

    public function update(Request $request, $codpessoa, $codpessoatelefone)
    {
        $data = $request->all();
        $telefone = PessoaTelefone::findOrFail($codpessoatelefone);
        $telefone = PessoaTelefoneService::update($telefone, $data);
        return new PessoaTelefoneResource($telefone);
    }

    public function delete(Request $request, $codpessoa, $codpessoatelefone)
    {

        $pessoa = PessoaTelefone::findOrFail($codpessoatelefone);
        $pessoa = PessoaTelefoneService::delete($pessoa);
        return response()->json([
            'result' => $pessoa
        ], 200);
    }

    public function cima(Request $request, $codpessoa, $codpessoatelefone)
    {
        $ptt = PessoaTelefone::findOrFail($codpessoatelefone);
        $ptt = PessoaTelefoneService::cima($ptt);
        $pes = $ptt->Pessoa->PessoaTelefoneS()->orderBy('ordem', 'asc')->get();
        return PessoaTelefoneResource::collection($pes);
    }

    public function baixo(Request $request, $codpessoa, $codpessoatelefone)
    {
        $ptt = PessoaTelefone::findOrFail($codpessoatelefone);
        $ptt = PessoaTelefoneService::baixo($ptt);
        $pes = $ptt->Pessoa->PessoaTelefoneS()->orderBy('ordem', 'asc')->get();

        return PessoaTelefoneResource::collection($pes);
    }

    public function ativar(Request $request, $codpessoa, $codpessoatelefone)
    {
        $pes = PessoaTelefone::findOrFail($codpessoatelefone);
        $pes = PessoaTelefoneService::ativar($pes);

        return new PessoaTelefoneResource($pes);
    }

    public function inativar(Request $request, $codpessoa, $codpessoatelefone)
    {
        $pes = PessoaTelefone::findOrFail($codpessoatelefone);
        $pes = PessoaTelefoneService::inativar($pes);

        return new PessoaTelefoneResource($pes);
    }

    public function verificar(Request $request, $codpessoa, $codpessoatelefone)
    {
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        $telverificar = PessoaTelefoneService::verificarsms($tel);
        return $telverificar;
    }

    public function confirmaVerificacao(Request $request, $codpessoa, $codpessoatelefone)
    {
        $request->validate([
            'codverificacao' => ['required']
        ]);
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        $tel = PessoaTelefoneService::confirmaVerificacao($tel, $request->codverificacao);
        return new PessoaTelefoneResource($tel);
    }
}
