<?php

namespace Mg\Cultura;

use App\Http\Requests\Mg\Cultura\VariedadeStoreRequest;
use App\Http\Requests\Mg\Cultura\VariedadeUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class VariedadeController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = VariedadeService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return VariedadeResource::collection($res);
    }

    public function store(VariedadeStoreRequest $request)
    {
        $model = new Variedade();
        $model->fill($request->validated());
        $model->save();

        return new VariedadeResource($model->fresh('Cultura'));
    }

    public function show(Request $request, $id)
    {
        return new VariedadeResource(Variedade::with('Cultura')->findOrFail($id));
    }

    public function update(VariedadeUpdateRequest $request, $id)
    {
        $model = Variedade::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new VariedadeResource($model->fresh('Cultura'));
    }

    public function destroy($id)
    {
        Variedade::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        $model = Variedade::findOrFail($id);
        VariedadeService::inativar($model);
        return new VariedadeResource($model->fresh('Cultura'));
    }

    public function ativar(Request $request, $id)
    {
        $model = Variedade::findOrFail($id);
        VariedadeService::ativar($model);
        return new VariedadeResource($model->fresh('Cultura'));
    }
}
