<?php

namespace Mg\Titulo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class TituloController extends MgController
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $paginator = TituloListagemService::listar($request->only([
            'codtitulo', 'numero', 'fatura', 'nossonumero',
            'codfilial', 'codpessoa', 'codgrupoeconomico', 'codtipotitulo',
            'codcontacontabil', 'codportador', 'codgrupocliente',
            'codusuariocriacao',
            'vencimento_de', 'vencimento_ate',
            'emissao_de', 'emissao_ate',
            'criacao_de', 'criacao_ate',
            'liquidacao_de', 'liquidacao_ate',
            'debito_de', 'debito_ate',
            'credito_de', 'credito_ate',
            'saldo_de', 'saldo_ate',
            'status', 'credito', 'gerencial', 'boleto',
            'pagarreceber', 'ordem',
        ]));

        return TituloListaResource::collection($paginator);
    }

    public function show($codtitulo)
    {
        Autorizador::autoriza(self::GRUPOS);
        $titulo = TituloService::carregar((int)$codtitulo);
        return new TituloDetalheResource($titulo);
    }

    public function store(TituloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $titulo = TituloService::criar($request->validated());
            DB::commit();
            return new TituloDetalheResource($titulo);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(TituloUpdateRequest $request, $codtitulo)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $titulo = Titulo::findOrFail($codtitulo);
            $titulo = TituloService::atualizar($titulo, $request->validated());
            DB::commit();
            return new TituloDetalheResource($titulo);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function estornar($codtitulo)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $titulo = Titulo::findOrFail($codtitulo);
            $titulo = TituloService::estornar($titulo);
            DB::commit();
            return new TituloDetalheResource($titulo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
