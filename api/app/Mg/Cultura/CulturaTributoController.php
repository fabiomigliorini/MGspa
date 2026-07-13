<?php

namespace Mg\Cultura;

use App\Http\Requests\Mg\Cultura\CulturaTributoStoreRequest;
use App\Http\Requests\Mg\Cultura\CulturaTributoUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class CulturaTributoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = CulturaTributoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return CulturaTributoResource::collection($res);
    }

    public function store(CulturaTributoStoreRequest $request)
    {
        $model = new CulturaTributo();
        $model->fill($request->validated());
        $model->save();

        return new CulturaTributoResource($model->fresh(['Tributo', 'UnidadeReferencia']));
    }

    public function show(Request $request, $id)
    {
        return new CulturaTributoResource(
            CulturaTributo::with(['Tributo', 'UnidadeReferencia'])->findOrFail($id)
        );
    }

    public function update(CulturaTributoUpdateRequest $request, $id)
    {
        $model = CulturaTributo::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new CulturaTributoResource($model->fresh(['Tributo', 'UnidadeReferencia']));
    }

    public function destroy($id)
    {
        CulturaTributo::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        $model = CulturaTributo::findOrFail($id);
        CulturaTributoService::inativar($model);
        return new CulturaTributoResource($model->fresh(['Tributo', 'UnidadeReferencia']));
    }

    public function ativar(Request $request, $id)
    {
        $model = CulturaTributo::findOrFail($id);
        CulturaTributoService::ativar($model);
        return new CulturaTributoResource($model->fresh(['Tributo', 'UnidadeReferencia']));
    }
}
