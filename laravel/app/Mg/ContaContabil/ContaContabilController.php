<?php

namespace Mg\ContaContabil;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class ContaContabilController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $paginator = ContaContabilService::listar($request->only([
            'codcontacontabil', 'contacontabil', 'numero', 'inativo',
        ]));

        return ContaContabilResource::collection($paginator);
    }

    public function show(int $codcontacontabil)
    {
        Autorizador::autoriza(self::GRUPOS);
        return new ContaContabilResource(ContaContabil::findOrFail($codcontacontabil));
    }

    public function store(ContaContabilStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $conta = ContaContabilService::criar($request->validated());
            DB::commit();
            return new ContaContabilResource($conta);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(int $codcontacontabil, ContaContabilUpdateRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $conta = ContaContabil::findOrFail($codcontacontabil);
            $conta = ContaContabilService::atualizar($conta, $request->validated());
            DB::commit();
            return new ContaContabilResource($conta);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function inativar(int $codcontacontabil)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $conta = ContaContabil::findOrFail($codcontacontabil);
            $conta = ContaContabilService::inativar($conta);
            DB::commit();
            return new ContaContabilResource($conta);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ativar(int $codcontacontabil)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $conta = ContaContabil::findOrFail($codcontacontabil);
            $conta = ContaContabilService::ativar($conta);
            DB::commit();
            return new ContaContabilResource($conta);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codcontacontabil)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $conta = ContaContabil::findOrFail($codcontacontabil);
            ContaContabilService::excluir($conta);
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
