<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
// use Mg\MgController;
// use DB;
// use Mg\FormaPagamento\FormaPagamento;
// use Mg\Usuario\Autorizador;
// use App\Rules\InscricaoEstadual;
// use Mg\Mercos\MercosCliente;

class PessoaAnexoController 
{

    public function upload(Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::upload($codpessoa, $request->nome, $request->base64);
        return new PessoaAnexoResource($pessoa);
    }

    public function index($codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        return new PessoaAnexoResource($pessoa);
    }

    public function get($codpessoa, $status, $arquivo)
    {
        $arq = PessoaAnexoService::response($codpessoa, $status, $arquivo);
        return $arq;
    }

    public function update(Request $request, $codpessoa, $nome)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::update($codpessoa, $nome, $request->label, $request->observacoes??null);
        return new PessoaAnexoResource($pessoa);
    }

    public function inativar(Request $request, $codpessoa, $nome)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::inativar($codpessoa, $nome);
        return new PessoaAnexoResource($pessoa);
    }

    public function ativar(Request $request, $codpessoa, $nome)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::ativar($codpessoa, $nome);
        return new PessoaAnexoResource($pessoa);
    }

    public function delete(Request $request, $codpessoa, $nome)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::delete($codpessoa, $nome);
        return new PessoaAnexoResource($pessoa);
    }
}
