<?php

namespace Mg\Cultura;

use App\Http\Requests\Mg\Cultura\VariedadeStoreRequest;
use App\Http\Requests\Mg\Cultura\VariedadeUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class VariedadeController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        [$filter, $sort, $fields] = $this->filtros($request);
        $res = VariedadeService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return VariedadeResource::collection($res);
    }

    public function store(VariedadeStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = new Variedade();
        $model->fill($request->validated());
        $model->save();

        return new VariedadeResource($model->fresh('Cultura'));
    }

    public function show(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        return new VariedadeResource(Variedade::with('Cultura')->findOrFail($id));
    }

    public function update(VariedadeUpdateRequest $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = Variedade::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new VariedadeResource($model->fresh('Cultura'));
    }

    public function destroy($id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $variedade = Variedade::findOrFail($id);
        try {
            $variedade->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                abort(409, 'Existem plantios vinculados a esta Variedade! Impossível excluir!');
            }
            throw $e;
        }
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = Variedade::findOrFail($id);
        VariedadeService::inativar($model);
        return new VariedadeResource($model->fresh('Cultura'));
    }

    public function ativar(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = Variedade::findOrFail($id);
        VariedadeService::ativar($model);
        return new VariedadeResource($model->fresh('Cultura'));
    }
}
