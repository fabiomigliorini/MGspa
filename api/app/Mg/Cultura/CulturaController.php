<?php

namespace Mg\Cultura;

use App\Http\Requests\Mg\Cultura\CulturaStoreRequest;
use App\Http\Requests\Mg\Cultura\CulturaUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class CulturaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = CulturaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return CulturaResource::collection($res);
    }

    public function store(CulturaStoreRequest $request)
    {
        $model = new Cultura();
        $model->fill($request->validated());
        $model->save();

        return new CulturaResource($model->fresh());
    }

    public function show(Request $request, $id)
    {
        return new CulturaResource(Cultura::findOrFail($id));
    }

    public function update(CulturaUpdateRequest $request, $id)
    {
        $model = Cultura::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new CulturaResource($model->fresh());
    }

    public function destroy($id)
    {
        Cultura::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function resumo(Request $request, $id)
    {
        return response()->json(CulturaService::resumo($id), 200);
    }

    public function inativar(Request $request, $id)
    {
        $model = Cultura::findOrFail($id);
        CulturaService::inativar($model);
        return new CulturaResource($model->fresh());
    }

    public function ativar(Request $request, $id)
    {
        $model = Cultura::findOrFail($id);
        CulturaService::ativar($model);
        return new CulturaResource($model->fresh());
    }
}
