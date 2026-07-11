<?php

namespace Mg\Fazenda;

use App\Http\Requests\Mg\Fazenda\FazendaStoreRequest;
use App\Http\Requests\Mg\Fazenda\FazendaUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Mg\MgController;

class FazendaController extends MgController
{
    const WITH = ['Pessoa'];

    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = FazendaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return FazendaResource::collection($res);
    }

    public function store(FazendaStoreRequest $request)
    {
        $model = new Fazenda();
        $model->fill($request->validated());
        $model->save();

        return new FazendaResource($model->fresh(static::WITH));
    }

    public function show(Request $request, $id)
    {
        return new FazendaResource(Fazenda::with(static::WITH)->findOrFail($id));
    }

    public function resumo(Request $request, $id)
    {
        return response()->json(FazendaService::resumo($id), 200);
    }

    public function update(FazendaUpdateRequest $request, $id)
    {
        $model = Fazenda::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new FazendaResource($model->fresh(static::WITH));
    }

    public function destroy($id)
    {
        $fazenda = Fazenda::findOrFail($id);
        try {
            $fazenda->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) !== '23503') {
                throw $e;
            }
            $msg = $e->getMessage();
            abort(409, match (true) {
                str_contains($msg, 'tbltalhao') => 'Existem talhões vinculados a esta Fazenda! Impossível excluir!',
                str_contains($msg, 'tblplantio') => 'Existem plantios vinculados a esta Fazenda! Impossível excluir!',
                default => 'Existem registros vinculados a esta Fazenda! Impossível excluir!',
            });
        }
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        $model = Fazenda::findOrFail($id);
        FazendaService::inativar($model);
        return new FazendaResource($model->fresh(static::WITH));
    }

    public function ativar(Request $request, $id)
    {
        $model = Fazenda::findOrFail($id);
        FazendaService::ativar($model);
        return new FazendaResource($model->fresh(static::WITH));
    }
}
