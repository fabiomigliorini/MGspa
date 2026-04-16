<?php

namespace Mg\FormaPagamento;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class FormaPagamentoController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $result = FormaPagamentoService::listar($request->only([
            'codformapagamento', 'formapagamento', 'inativo',
            'avista', 'boleto', 'fechamento', 'notafiscal', 'entrega',
            'valecompra', 'lio', 'pix', 'stone', 'integracao', 'safrapay',
            'todos',
        ]));

        return FormaPagamentoResource::collection($result);
    }

    public function show(int $codformapagamento)
    {
        Autorizador::autoriza(self::GRUPOS);
        return new FormaPagamentoResource(FormaPagamento::findOrFail($codformapagamento));
    }

    public function store(FormaPagamentoStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $forma = FormaPagamentoService::criar($request->validated());
            DB::commit();
            return new FormaPagamentoResource($forma);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(int $codformapagamento, FormaPagamentoUpdateRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $forma = FormaPagamento::findOrFail($codformapagamento);
            $forma = FormaPagamentoService::atualizar($forma, $request->validated());
            DB::commit();
            return new FormaPagamentoResource($forma);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function inativar(int $codformapagamento)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $forma = FormaPagamento::findOrFail($codformapagamento);
            $forma = FormaPagamentoService::inativar($forma);
            DB::commit();
            return new FormaPagamentoResource($forma);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ativar(int $codformapagamento)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $forma = FormaPagamento::findOrFail($codformapagamento);
            $forma = FormaPagamentoService::ativar($forma);
            DB::commit();
            return new FormaPagamentoResource($forma);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codformapagamento)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $forma = FormaPagamento::findOrFail($codformapagamento);
            FormaPagamentoService::excluir($forma);
            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
