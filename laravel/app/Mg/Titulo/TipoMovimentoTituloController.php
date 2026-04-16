<?php

namespace Mg\Titulo;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class TipoMovimentoTituloController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $result = TipoMovimentoTituloService::listar($request->only([
            'codtipomovimentotitulo', 'tipomovimentotitulo',
            'implantacao', 'ajuste', 'armotizacao', 'juros',
            'desconto', 'pagamento', 'estorno', 'inativo', 'todos',
        ]));

        return TipoMovimentoTituloResource::collection($result);
    }

    public function show(int $codtipomovimentotitulo)
    {
        Autorizador::autoriza(self::GRUPOS);
        return new TipoMovimentoTituloResource(TipoMovimentoTitulo::findOrFail($codtipomovimentotitulo));
    }

    public function store(TipoMovimentoTituloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoMovimentoTituloService::criar($request->validated());
            DB::commit();
            return new TipoMovimentoTituloResource($tipo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(int $codtipomovimentotitulo, TipoMovimentoTituloUpdateRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoMovimentoTitulo::findOrFail($codtipomovimentotitulo);
            $tipo = TipoMovimentoTituloService::atualizar($tipo, $request->validated());
            DB::commit();
            return new TipoMovimentoTituloResource($tipo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function inativar(int $codtipomovimentotitulo)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoMovimentoTitulo::findOrFail($codtipomovimentotitulo);
            $tipo = TipoMovimentoTituloService::inativar($tipo);
            DB::commit();
            return new TipoMovimentoTituloResource($tipo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ativar(int $codtipomovimentotitulo)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoMovimentoTitulo::findOrFail($codtipomovimentotitulo);
            $tipo = TipoMovimentoTituloService::ativar($tipo);
            DB::commit();
            return new TipoMovimentoTituloResource($tipo);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codtipomovimentotitulo)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $tipo = TipoMovimentoTitulo::findOrFail($codtipomovimentotitulo);
            TipoMovimentoTituloService::excluir($tipo);
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
