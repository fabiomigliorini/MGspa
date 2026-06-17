<?php

namespace Mg\Safra;

use App\Http\Requests\Mg\Safra\SafraStoreRequest;
use App\Http\Requests\Mg\Safra\SafraUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class SafraController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = SafraService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return SafraResource::collection($res);
    }

    public function store(SafraStoreRequest $request)
    {
        $model = new Safra();
        $model->fill($request->validated());
        $model->save();

        return new SafraResource($model->fresh('Cultura'));
    }

    public function show(Request $request, $id)
    {
        return new SafraResource(Safra::with('Cultura')->findOrFail($id));
    }

    /**
     * KPIs comerciais (rollup dos contratos da safra). Retorna array computado,
     * não um registro — por isso fica em response()->json, sem Resource.
     */
    public function comercial(Request $request, $id)
    {
        return response()->json(SafraService::resumoComercial((int) $id), 200);
    }

    public function update(SafraUpdateRequest $request, $id)
    {
        $model = Safra::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new SafraResource($model->fresh('Cultura'));
    }

    public function destroy($id)
    {
        Safra::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        SafraService::inativar(Safra::findOrFail($id));
        return new SafraResource(Safra::with('Cultura')->findOrFail($id));
    }

    public function ativar(Request $request, $id)
    {
        SafraService::ativar(Safra::findOrFail($id));
        return new SafraResource(Safra::with('Cultura')->findOrFail($id));
    }
}
