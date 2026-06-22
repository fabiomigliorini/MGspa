<?php

namespace Mg\Moeda;

use App\Http\Requests\Mg\Moeda\MoedaStoreRequest;
use App\Http\Requests\Mg\Moeda\MoedaUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\MgService;

class MoedaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = MoedaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return MoedaResource::collection($res);
    }

    public function show(Request $request, $moeda)
    {
        return new MoedaResource(Moeda::findOrFail($moeda));
    }

    public function store(MoedaStoreRequest $request)
    {
        $model = new Moeda();
        $model->fill($request->validated());
        $model->save();
        return new MoedaResource($model);
    }

    public function update(MoedaUpdateRequest $request, $moeda)
    {
        $model = Moeda::findOrFail($moeda);
        $model->fill($request->validated());
        $model->update();
        return new MoedaResource($model);
    }

    public function destroy($moeda)
    {
        $model = Moeda::findOrFail($moeda);
        try {
            $model->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) !== '23503') {
                throw $e;
            }
            abort(409, 'Existem fixações de contrato usando esta Moeda! Impossível excluir!');
        }
        return response()->noContent();
    }

    public function inativar(Request $request, $moeda)
    {
        MgService::inativar(Moeda::findOrFail($moeda));
        return new MoedaResource(Moeda::findOrFail($moeda));
    }

    public function ativar(Request $request, $moeda)
    {
        MgService::ativar(Moeda::findOrFail($moeda));
        return new MoedaResource(Moeda::findOrFail($moeda));
    }
}
