<?php

namespace Mg\Colaborador;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Mg\Usuario\Autorizador;

class ColaboradorCartaoController extends Controller
{
    public function index(int $codpessoa)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $regs = ColaboradorCartao::whereIn(
            'codcolaborador',
            Colaborador::where('codpessoa', $codpessoa)->pluck('codcolaborador')
        )->orderBy('codcolaboradorcartao', 'desc')->get();

        return ColaboradorCartaoResource::collection($regs);
    }

    public function create(int $codpessoa, ColaboradorCartaoStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $dados = $request->validated();
        $this->assertColaboradorDaPessoa($dados['codcolaborador'], $codpessoa);
        $this->assertNumeroInedito($dados['codcolaborador'], $dados['numero']);

        $reg = ColaboradorCartao::create($dados);

        return (new ColaboradorCartaoResource($reg->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(int $codpessoa, int $codcolaboradorcartao, ColaboradorCartaoUpdateRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = ColaboradorCartao::findOrFail($codcolaboradorcartao);
        $this->assertColaboradorDaPessoa($reg->codcolaborador, $codpessoa);

        $dados = $request->validated();

        // O numero do cartao e' IMUTAVEL — nunca se sobrescreve o gravado. O
        // UpdateRequest ja' rejeita a chave (`prohibited`); o unset aqui e' o
        // segundo cinto, pra edicao NENHUMA conseguir trocar o numero.
        unset($dados['numero']);

        $reg->update($dados);

        return new ColaboradorCartaoResource($reg->refresh());
    }

    public function inativar(int $codcolaboradorcartao)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = ColaboradorCartao::findOrFail($codcolaboradorcartao);
        $reg->inativo = Carbon::now();
        $reg->update();

        return new ColaboradorCartaoResource($reg->refresh());
    }

    public function ativar(int $codcolaboradorcartao)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = ColaboradorCartao::findOrFail($codcolaboradorcartao);
        $reg->inativo = null;
        $reg->update();

        return new ColaboradorCartaoResource($reg->refresh());
    }

    // ------------------------------------------------------------------
    // O cartao so' pode ser cadastrado/alterado sob a pessoa dona do vinculo.
    private function assertColaboradorDaPessoa(int $codcolaborador, int $codpessoa): void
    {
        $ok = Colaborador::where('codcolaborador', $codcolaborador)
            ->where('codpessoa', $codpessoa)
            ->exists();

        if (!$ok) {
            abort(422, 'Colaborador nao pertence a esta pessoa.');
        }
    }

    // numero e' `encrypted` (IV aleatorio => ciphertext muda a cada save), entao
    // nao da' pra checar duplicidade em SQL: decripta os poucos cartoes ativos
    // do colaborador e compara os digitos em PHP. So' o create chama — na
    // edicao o numero nao muda, entao nao ha' o que conferir.
    private function assertNumeroInedito(int $codcolaborador, string $numero): void
    {
        $digitos = preg_replace('/\D/', '', $numero);

        $query = ColaboradorCartao::where('codcolaborador', $codcolaborador)
            ->whereNull('inativo');

        foreach ($query->get() as $cartao) {
            if (preg_replace('/\D/', '', (string) $cartao->numero) === $digitos) {
                abort(422, 'Ja existe um cartao ativo com este numero para o colaborador.');
            }
        }
    }
}
