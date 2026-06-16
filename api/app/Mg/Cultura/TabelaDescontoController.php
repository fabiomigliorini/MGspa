<?php

namespace Mg\Cultura;

use App\Http\Requests\Mg\Cultura\TabelaDescontoStoreRequest;
use App\Http\Requests\Mg\Cultura\TabelaDescontoUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class TabelaDescontoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = TabelaDescontoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return TabelaDescontoResource::collection($res);
    }

    public function store(TabelaDescontoStoreRequest $request)
    {
        $model = new TabelaDesconto();
        $model->fill($request->validated());
        $model->save();

        return new TabelaDescontoResource($model->fresh('Cultura'));
    }

    public function show(Request $request, $id)
    {
        return new TabelaDescontoResource(TabelaDesconto::with('Cultura')->findOrFail($id));
    }

    public function update(TabelaDescontoUpdateRequest $request, $id)
    {
        $model = TabelaDesconto::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new TabelaDescontoResource($model->fresh('Cultura'));
    }

    public function destroy($id)
    {
        TabelaDesconto::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        $model = TabelaDesconto::findOrFail($id);
        TabelaDescontoService::inativar($model);
        return new TabelaDescontoResource($model->fresh('Cultura'));
    }

    public function ativar(Request $request, $id)
    {
        $model = TabelaDesconto::findOrFail($id);
        TabelaDescontoService::ativar($model);
        return new TabelaDescontoResource($model->fresh('Cultura'));
    }
}
