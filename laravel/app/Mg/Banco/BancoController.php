<?php

namespace Mg\Banco;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class BancoController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $paginator = BancoService::listar($request->only([
            'codbanco', 'banco', 'sigla', 'numerobanco', 'inativo',
        ]));

        return BancoResource::collection($paginator);
    }

    public function show(int $codbanco)
    {
        Autorizador::autoriza(self::GRUPOS);
        return new BancoResource(Banco::findOrFail($codbanco));
    }

    public function store(BancoStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $banco = BancoService::criar($request->validated());
            DB::commit();
            return new BancoResource($banco);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(int $codbanco, BancoUpdateRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $banco = Banco::findOrFail($codbanco);
            $banco = BancoService::atualizar($banco, $request->validated());
            DB::commit();
            return new BancoResource($banco);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function inativar(int $codbanco)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $banco = Banco::findOrFail($codbanco);
            $banco = BancoService::inativar($banco);
            DB::commit();
            return new BancoResource($banco);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ativar(int $codbanco)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $banco = Banco::findOrFail($codbanco);
            $banco = BancoService::ativar($banco);
            DB::commit();
            return new BancoResource($banco);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codbanco)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $banco = Banco::findOrFail($codbanco);
            BancoService::excluir($banco);
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
