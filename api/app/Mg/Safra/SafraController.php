<?php

namespace Mg\Safra;

use App\Http\Requests\Mg\Safra\SafraStoreRequest;
use App\Http\Requests\Mg\Safra\SafraUpdateRequest;
use Illuminate\Database\QueryException;
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
        $safra = Safra::findOrFail($id);
        try {
            $safra->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) !== '23503') {
                throw $e;
            }
            $msg = $e->getMessage();
            abort(409, match (true) {
                str_contains($msg, 'tblplantio') => 'Existem plantios vinculados a esta Safra! Impossível excluir!',
                str_contains($msg, 'tblcontrato') => 'Existem contratos vinculados a esta Safra! Impossível excluir!',
                str_contains($msg, 'tblcarga') => 'Existem cargas vinculadas a esta Safra! Impossível excluir!',
                str_contains($msg, 'tblmovimentograo') => 'Existe movimentação de grão vinculada a esta Safra! Impossível excluir!',
                default => 'Existem registros vinculados a esta Safra! Impossível excluir!',
            });
        }
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
