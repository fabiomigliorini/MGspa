<?php

namespace Mg\Embarque;

use App\Http\Requests\Mg\Embarque\EmbarqueSincronizarRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class EmbarqueController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = EmbarqueService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return EmbarqueResource::collection($res);
    }

    public function show(Request $request, $id)
    {
        return new EmbarqueResource(Embarque::with(EmbarqueService::WITH)->findOrFail($id));
    }

    /**
     * Sincroniza um embarque criado/editado offline (upsert por uuid). Aceita
     * parcial — pesos/classificacao/NF chegam ao longo das etapas do patio.
     */
    public function sincronizar(EmbarqueSincronizarRequest $request)
    {
        $embarque = EmbarqueService::sincronizar($request->all());

        return new EmbarqueResource($embarque->load(EmbarqueService::WITH));
    }

    public function inativar(Request $request, $id)
    {
        $model = EmbarqueService::inativar(Embarque::findOrFail($id));
        return new EmbarqueResource($model->fresh(EmbarqueService::WITH));
    }

    public function ativar(Request $request, $id)
    {
        $model = EmbarqueService::ativar(Embarque::findOrFail($id));
        return new EmbarqueResource($model->fresh(EmbarqueService::WITH));
    }
}
