<?php

namespace Mg\Vale;

use App\Http\Requests\Mg\Vale\ValeModeloStoreRequest;
use App\Http\Requests\Mg\Vale\ValeModeloUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\MgController;
use Mg\MgService;
use Mg\Usuario\Autorizador;

class ValeModeloController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        [$filter, $sort, $fields] = $this->filtros($request);
        $res = ValeModeloService::pesquisar($filter, $sort, $fields)
            ->with('ValeModeloProdutoBarraS.ProdutoBarra')
            ->paginate()
            ->appends($request->all());

        return ValeModeloResource::collection($res);
    }

    public function show(Request $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        return new ValeModeloResource(ValeModelo::findOrFail($valeModelo));
    }

    public function store(ValeModeloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = DB::transaction(function () use ($request) {
            return ValeModeloService::salvar($request->validated());
        });

        return new ValeModeloResource($model);
    }

    public function update(ValeModeloUpdateRequest $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ValeModelo::findOrFail($valeModelo);
        $model = DB::transaction(function () use ($request, $model) {
            return ValeModeloService::salvar($request->validated(), $model);
        });

        return new ValeModeloResource($model);
    }

    public function destroy($valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ValeModelo::findOrFail($valeModelo);
        try {
            DB::transaction(function () use ($model) {
                $model->ValeModeloProdutoBarraS()->delete();
                $model->delete();
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) !== '23503') {
                throw $e;
            }
            abort(409, 'Existem vales emitidos com este Modelo! Inative em vez de excluir!');
        }

        return response()->noContent();
    }

    public function inativar(Request $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        MgService::inativar(ValeModelo::findOrFail($valeModelo));
        return new ValeModeloResource(ValeModelo::findOrFail($valeModelo));
    }

    public function ativar(Request $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        MgService::ativar(ValeModelo::findOrFail($valeModelo));
        return new ValeModeloResource(ValeModelo::findOrFail($valeModelo));
    }
}
