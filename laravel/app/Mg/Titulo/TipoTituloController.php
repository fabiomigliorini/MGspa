<?php

namespace Mg\Titulo;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class TipoTituloController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $result = TipoTituloService::listar($request->only([
            'codtipotitulo', 'tipotitulo', 'codtipomovimentotitulo',
            'pagar', 'receber', 'debito', 'credito', 'inativo', 'todos',
        ]));

        return TipoTituloResource::collection($result);
    }

    public function show(int $codtipotitulo)
    {
        Autorizador::autoriza(self::GRUPOS);
        return new TipoTituloResource(TipoTitulo::with('TipoMovimentoTitulo')->findOrFail($codtipotitulo));
    }

    public function store(TipoTituloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoTituloService::criar($request->validated());
            DB::commit();
            return new TipoTituloResource($tipo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(int $codtipotitulo, TipoTituloUpdateRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoTitulo::findOrFail($codtipotitulo);
            $tipo = TipoTituloService::atualizar($tipo, $request->validated());
            DB::commit();
            return new TipoTituloResource($tipo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function inativar(int $codtipotitulo)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoTitulo::findOrFail($codtipotitulo);
            $tipo = TipoTituloService::inativar($tipo);
            DB::commit();
            return new TipoTituloResource($tipo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ativar(int $codtipotitulo)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoTitulo::findOrFail($codtipotitulo);
            $tipo = TipoTituloService::ativar($tipo);
            DB::commit();
            return new TipoTituloResource($tipo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codtipotitulo)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoTitulo::findOrFail($codtipotitulo);
            TipoTituloService::excluir($tipo);
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
