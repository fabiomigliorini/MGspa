<?php

namespace Mg\Pessoa;


use Mg\Usuario\Autorizador;
use Mg\Colaborador\Colaborador;
use Illuminate\Http\Request;

class PessoaAnexoController
{

    private function autorizarAnexo(int $codpessoa): void
    {
        if (Autorizador::pode(['Recursos Humanos'])) {
            return;
        }
        $colaborador = Colaborador::where('codpessoa', $codpessoa)
            ->whereNull('rescisao')
            ->exists();

        if ($colaborador) {
            abort(403, 'Documentos de Colaboradores são restritos ao Recursos Humanos.');
        }
        Autorizador::autoriza(['Financeiro']);
    }

    public function upload(Request $request, $codpessoa)
    {
        $this->autorizarAnexo($codpessoa);
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::upload($codpessoa, $request->nome, $request->base64);
        return new PessoaAnexoResource($pessoa);
    }

    public function index($codpessoa)
    {
        $this->autorizarAnexo($codpessoa);
        $pessoa = Pessoa::findOrFail($codpessoa);
        return new PessoaAnexoResource($pessoa);
    }

    public function get($codpessoa, $status, $arquivo)
    {
        $this->autorizarAnexo($codpessoa);
        $arq = PessoaAnexoService::response($codpessoa, $status, $arquivo);
        return $arq;
    }

    public function update(Request $request, $codpessoa, $nome)
    {
        $this->autorizarAnexo($codpessoa);
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::update($codpessoa, $nome, $request->label, $request->observacoes ?? null);
        return new PessoaAnexoResource($pessoa);
    }

    public function inativar(Request $request, $codpessoa, $nome)
    {
        $this->autorizarAnexo($codpessoa);
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::inativar($codpessoa, $nome);
        return new PessoaAnexoResource($pessoa);
    }

    public function ativar(Request $request, $codpessoa, $nome)
    {
        $this->autorizarAnexo($codpessoa);
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::ativar($codpessoa, $nome);
        return new PessoaAnexoResource($pessoa);
    }

    public function delete(Request $request, $codpessoa, $nome)
    {
        $this->autorizarAnexo($codpessoa);
        $pessoa = Pessoa::findOrFail($codpessoa);
        PessoaAnexoService::delete($codpessoa, $nome);
        return new PessoaAnexoResource($pessoa);
    }
}
