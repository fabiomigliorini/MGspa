<?php

namespace Mg\Titulo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class TituloController extends MgController
{
    // Visualização: qualquer usuário autenticado.
    private const GRUPOS_LEITURA = ['Administrador', 'Financeiro', 'Cobranca', 'Publico'];
    // Mutação (criar/alterar/estornar): apenas financeiro/cobrança/admin.
    private const GRUPOS_MUTACAO = ['Administrador', 'Financeiro', 'Cobranca'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);

        $paginator = TituloListagemService::listar($request->only([
            'codtitulo',
            'numero',
            'fatura',
            'nossonumero',
            'codfilial',
            'codpessoa',
            'codgrupoeconomico',
            'codtipotitulo',
            'codcontacontabil',
            'codportador',
            'codgrupocliente',
            'codusuariocriacao',
            'vencimento_de',
            'vencimento_ate',
            'emissao_de',
            'emissao_ate',
            'criacao_de',
            'criacao_ate',
            'liquidacao_de',
            'liquidacao_ate',
            'debito_de',
            'debito_ate',
            'credito_de',
            'credito_ate',
            'saldo_de',
            'saldo_ate',
            'status',
            'credito',
            'gerencial',
            'boleto',
            'pagarreceber',
            'ordem',
        ]));

        return TituloListaResource::collection($paginator);
    }

    public function abertosParaFechamento(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $rows = TituloAbertosFechamentoService::listar($request->only([
            'codpessoa', 'codgrupoeconomico', 'codfilial',
            'vencimento_de', 'vencimento_ate', 'credito',
            'codtipotitulo', 'codcontacontabil', 'codportador',
        ]));
        return response()->json(['data' => $rows]);
    }

    public function show($codtitulo)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $titulo = TituloService::carregar((int)$codtitulo);
        return new TituloDetalheResource($titulo);
    }

    public function store(TituloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

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
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

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
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

        // título gerado automaticamente (negócio/agrupamento) só pode ser estornado
        // pela origem que o criou.
        $titulo = Titulo::findOrFail($codtitulo);
        if (!empty($titulo->codnegocioformapagamento) || !empty($titulo->codtituloagrupamento)) {
            throw new \Exception("Título gerado automaticamente não pode ser estornado individualmente!", 1);
        }

        DB::beginTransaction();
        try {
            $titulo = TituloService::estornar($titulo);
            DB::commit();
            return new TituloDetalheResource($titulo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
