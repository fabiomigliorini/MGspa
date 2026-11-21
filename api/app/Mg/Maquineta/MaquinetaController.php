<?php

namespace Mg\Maquineta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;
use SimpleSoftwareIO\QrCode\Generator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MaquinetaController extends Controller
{
    const GRUPOS = ['Financeiro', 'Gerente'];

    public function index(Request $request)
    {
        Autorizador::autoriza(static::GRUPOS);
        // todas de uma vez: a tela agrupa por filial e adquirente, como o painel do portador
        $maquinetas = MaquinetaService::listar($request->only([
            'codmaquineta', 'texto', 'codfilial', 'codpessoa', 'integracao', 'inativo',
        ]));
        // situacao dos periodos (conferencia do cartao, M9.8)
        $resumo = MaquinetaLoteService::resumo($maquinetas->pluck('codmaquineta')->all());
        $maquinetas->each(fn ($m) => $m->periodos = $resumo[$m->codmaquineta] ?? null);
        return MaquinetaResource::collection($maquinetas);
    }

    public function adquirentes()
    {
        Autorizador::autoriza(static::GRUPOS);
        return response()->json(MaquinetaService::adquirentes());
    }

    public function show($codmaquineta)
    {
        Autorizador::autoriza(static::GRUPOS);
        $maquineta = Maquineta::with(MaquinetaService::RELACOES)->findOrFail($codmaquineta);
        return new MaquinetaResource($maquineta);
    }

    public function store(MaquinetaStoreRequest $request)
    {
        $dados = $request->validated();
        MaquinetaService::autorizar($dados['codfilial']);
        $maquineta = DB::transaction(fn () => MaquinetaService::criar($dados));
        return new MaquinetaResource($maquineta);
    }

    public function update(MaquinetaUpdateRequest $request, $codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        $dados = $request->validated();
        MaquinetaService::autorizar($maquineta->codfilial);
        MaquinetaService::autorizar($dados['codfilial']);
        $maquineta = DB::transaction(fn () => MaquinetaService::atualizar($maquineta, $dados));
        return new MaquinetaResource($maquineta);
    }

    public function destroy($codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        MaquinetaService::autorizar($maquineta->codfilial);
        DB::transaction(fn () => MaquinetaService::excluir($maquineta));
        return response()->noContent();
    }

    public function inativar($codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        MaquinetaService::autorizar($maquineta->codfilial);
        $maquineta = DB::transaction(fn () => MaquinetaService::inativar($maquineta));
        return new MaquinetaResource($maquineta);
    }

    public function ativar($codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        MaquinetaService::autorizar($maquineta->codfilial);
        $maquineta = DB::transaction(fn () => MaquinetaService::ativar($maquineta));
        return new MaquinetaResource($maquineta);
    }

    public function juntar(Request $request, $codmaquineta)
    {
        $request->validate([
            'codmaquinetadestino' => 'required|integer|exists:tblmaquineta,codmaquineta',
        ]);
        $errada = Maquineta::findOrFail($codmaquineta);
        $certa = Maquineta::findOrFail($request->codmaquinetadestino);
        MaquinetaService::autorizar($errada->codfilial);
        $certa = DB::transaction(fn () => MaquinetaService::juntar($errada, $certa));
        return new MaquinetaResource($certa);
    }

    // Parear (SafraPay), passo 1: o QR Code do PDV Saurus da maquineta para o aparelho ler. O
    // mesmo botão serve para o primeiro pareamento e para o de novo (TASK-264).
    public function saurusQrCode(Request $request)
    {
        $request->validate([
            'codmaquineta' => 'required|integer|exists:tblmaquineta,codmaquineta',
        ]);
        $maquineta = Maquineta::findOrFail($request->codmaquineta);
        MaquinetaService::autorizar($maquineta->codfilial);

        try {
            $pdv = DB::transaction(fn () => MaquinetaService::qrCode($maquineta));
        } catch (HttpException $e) {
            throw $e; // recusa nossa (abort), com a mensagem
        } catch (\Exception $e) {
            abort(502, 'Falha ao acessar a API da Saurus. Tente de novo em instantes.');
        }

        $svg = (string) (new Generator)->format('svg')->size(220)->errorCorrection('H')
            ->generate($pdv->chavepublica);

        return response()->json([
            'qrcode' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ]);
    }

    // Parear (SafraPay), passo 2: o aparelho leu o QR Code e vira o aparelho ativo da maquineta
    public function saurusConfirmar(Request $request)
    {
        $request->validate([
            'codmaquineta' => 'required|integer|exists:tblmaquineta,codmaquineta',
        ]);
        $maquineta = Maquineta::findOrFail($request->codmaquineta);
        MaquinetaService::autorizar($maquineta->codfilial);

        try {
            $maquineta = DB::transaction(fn () => MaquinetaService::confirmarLeitura($maquineta));
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            abort(502, 'Falha ao acessar a API da Saurus. Tente de novo em instantes.');
        }
        return new MaquinetaResource($maquineta);
    }
}
