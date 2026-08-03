<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Mg\Colaborador\Colaborador;
use Mg\Filial\Filial;
use Mg\Usuario\Autorizador;

class PessoaCartaoController extends Controller
{
    public function index(int $codpessoa)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $regs = PessoaCartao::where('codpessoa', $codpessoa)
            ->orderBy('codpessoacartao', 'desc')
            ->get();

        return PessoaCartaoResource::collection($regs);
    }

    public function create(int $codpessoa, PessoaCartaoStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $this->assertTitularValido($codpessoa);

        $dados = $request->validated();
        $dados['codpessoa'] = $codpessoa;   // o titular e' a rota, nao o payload
        $this->assertNumeroInedito($codpessoa, $dados['numero']);

        $reg = PessoaCartao::create($dados);

        return (new PessoaCartaoResource($reg->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(int $codpessoa, int $codpessoacartao, PessoaCartaoUpdateRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = PessoaCartao::findOrFail($codpessoacartao);
        $this->assertCartaoDaPessoa($reg, $codpessoa);

        $dados = $request->validated();

        // O numero do cartao e' IMUTAVEL — nunca se sobrescreve o gravado. O
        // UpdateRequest ja' rejeita a chave (`prohibited`); o unset aqui e' o
        // segundo cinto, pra edicao NENHUMA conseguir trocar o numero.
        unset($dados['numero']);

        $reg->update($dados);

        return new PessoaCartaoResource($reg->refresh());
    }

    public function inativar(int $codpessoacartao)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = PessoaCartao::findOrFail($codpessoacartao);
        $reg->inativo = Carbon::now();
        $reg->update();

        return new PessoaCartaoResource($reg->refresh());
    }

    public function ativar(int $codpessoacartao)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = PessoaCartao::findOrFail($codpessoacartao);
        $reg->inativo = null;
        $reg->update();

        return new PessoaCartaoResource($reg->refresh());
    }

    // ------------------------------------------------------------------
    // Cartao so' existe para quem tem vinculo de colaborador ou para a pessoa
    // de uma filial (o cartao corporativo da loja — Centro, Botanico, Fazenda).
    private function assertTitularValido(int $codpessoa): void
    {
        $ok = Colaborador::where('codpessoa', $codpessoa)->exists()
            || Filial::where('codpessoa', $codpessoa)->exists();

        if (!$ok) {
            abort(422, 'Cartao so pode ser cadastrado para colaborador ou filial.');
        }
    }

    // O cartao so' pode ser alterado sob a pessoa dona dele.
    private function assertCartaoDaPessoa(PessoaCartao $reg, int $codpessoa): void
    {
        if ($reg->codpessoa !== $codpessoa) {
            abort(422, 'Cartao nao pertence a esta pessoa.');
        }
    }

    // numero e' `encrypted` (IV aleatorio => ciphertext muda a cada save), entao
    // nao da' pra checar duplicidade em SQL: decripta os poucos cartoes ativos
    // da pessoa e compara os digitos em PHP. So' o create chama — na edicao o
    // numero nao muda, entao nao ha' o que conferir.
    private function assertNumeroInedito(int $codpessoa, string $numero): void
    {
        $digitos = preg_replace('/\D/', '', $numero);

        $query = PessoaCartao::where('codpessoa', $codpessoa)
            ->whereNull('inativo');

        foreach ($query->get() as $cartao) {
            if (preg_replace('/\D/', '', (string) $cartao->numero) === $digitos) {
                abort(422, 'Ja existe um cartao ativo com este numero para a pessoa.');
            }
        }
    }
}
