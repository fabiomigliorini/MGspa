<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class GrupoClienteController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $result = GrupoClienteService::listar($request->only([
            'codgrupocliente', 'grupocliente', 'status', 'todos_sem_paginacao',
        ]));

        return GrupoClienteResource::collection($result);
    }

    public function show(int $codgrupocliente)
    {
        Autorizador::autoriza(self::GRUPOS);
        return new GrupoClienteResource(GrupoCliente::findOrFail($codgrupocliente));
    }

    public function store(GrupoClienteStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $grupo = GrupoClienteService::criar($request->validated());
            DB::commit();
            return new GrupoClienteResource($grupo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(int $codgrupocliente, GrupoClienteUpdateRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $grupo = GrupoCliente::findOrFail($codgrupocliente);
            $grupo = GrupoClienteService::atualizar($grupo, $request->validated());
            DB::commit();
            return new GrupoClienteResource($grupo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function inativar(int $codgrupocliente)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $grupo = GrupoCliente::findOrFail($codgrupocliente);
            $grupo = GrupoClienteService::inativar($grupo);
            DB::commit();
            return new GrupoClienteResource($grupo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ativar(int $codgrupocliente)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $grupo = GrupoCliente::findOrFail($codgrupocliente);
            $grupo = GrupoClienteService::ativar($grupo);
            DB::commit();
            return new GrupoClienteResource($grupo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codgrupocliente)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $grupo = GrupoCliente::findOrFail($codgrupocliente);
            GrupoClienteService::excluir($grupo);
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
