<?php

namespace Mg\Pessoa;

use Exception;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class PessoaTelefoneController extends MgController
{
    public function index(Request $request, $codpessoa)
    {
        $items = PessoaTelefone::where('codpessoa', $codpessoa)->orderBy('ordem', 'asc')->get();
        return PessoaTelefoneResource::collection($items);
    }

    public function create(Request $request, $codpessoa)
    {
        Autorizador::autoriza(['Publico']);

        $request->validate([
            'tipo' => ['required'],
            'pais' => ['required'],
            'telefone' => ['required'],
        ]);
        if ($request->tipo != 9) {
            $request->validate(['ddd' => ['required']]);
        }

        $data = $request->all();
        $data['codpessoa'] = $codpessoa;
        $pt = PessoaTelefoneService::create($data);
        $pts = $pt->Pessoa->PessoaTelefoneS()->orderBy('ordem', 'asc')->get();
        return PessoaTelefoneResource::collection($pts);
    }

    public function show(Request $request, $codpessoa, $codpessoatelefone)
    {
        $item = PessoaTelefone::findOrFail($codpessoatelefone);
        return new PessoaTelefoneResource($item);
    }

    public function update(Request $request, $codpessoa, $codpessoatelefone)
    {
        Autorizador::autoriza(['Publico']);
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        $tel = PessoaTelefoneService::update($tel, $request->all());
        return new PessoaTelefoneResource($tel);
    }

    public function delete(Request $request, $codpessoa, $codpessoatelefone)
    {
        Autorizador::autoriza(['Publico']);
        $count = PessoaTelefone::where('codpessoa', $codpessoa)->count();
        if ($count > 1) {
            $tel = PessoaTelefone::findOrFail($codpessoatelefone);
            PessoaTelefoneService::delete($tel);
            return response()->json(['result' => $tel], 200);
        }
        throw new Exception('Não é possivel excluir todos os telefones', 1);
    }

    public function cima(Request $request, $codpessoa, $codpessoatelefone)
    {
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        $tel = PessoaTelefoneService::cima($tel);
        $list = $tel->Pessoa->PessoaTelefoneS()->orderBy('ordem', 'asc')->get();
        return PessoaTelefoneResource::collection($list);
    }

    public function baixo(Request $request, $codpessoa, $codpessoatelefone)
    {
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        $tel = PessoaTelefoneService::baixo($tel);
        $list = $tel->Pessoa->PessoaTelefoneS()->orderBy('ordem', 'asc')->get();
        return PessoaTelefoneResource::collection($list);
    }

    public function ativar(Request $request, $codpessoa, $codpessoatelefone)
    {
        Autorizador::autoriza(['Publico']);
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        $tel = PessoaTelefoneService::ativar($tel);
        return new PessoaTelefoneResource($tel);
    }

    public function inativar(Request $request, $codpessoa, $codpessoatelefone)
    {
        Autorizador::autoriza(['Publico']);
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        $tel = PessoaTelefoneService::inativar($tel);
        return new PessoaTelefoneResource($tel);
    }

    public function verificar(Request $request, $codpessoa, $codpessoatelefone)
    {
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        return PessoaTelefoneService::verificarsms($tel);
    }

    public function confirmaVerificacao(Request $request, $codpessoa, $codpessoatelefone)
    {
        $request->validate(['codverificacao' => ['required']]);
        $tel = PessoaTelefone::findOrFail($codpessoatelefone);
        $tel = PessoaTelefoneService::confirmaVerificacao($tel, $request->codverificacao);
        return new PessoaTelefoneResource($tel);
    }
}
