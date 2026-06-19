<?php

namespace Mg\Grao;

use App\Http\Requests\Mg\Grao\CargaSincronizarRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class CargaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = CargaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return CargaResource::collection($res);
    }

    public function show(Request $request, $id)
    {
        return new CargaResource(Carga::with(CargaService::WITH)->findOrFail($id));
    }

    /**
     * Sincroniza uma carga criada/editada offline (upsert por uuid). Aceita
     * parcial — pesos/classificacao/pontos chegam ao longo das etapas do patio.
     */
    public function sincronizar(CargaSincronizarRequest $request)
    {
        $request->validated();
        $carga = CargaService::sincronizar($request->all());
        return new CargaResource($carga->load(CargaService::WITH));
    }

    public function inativar(Request $request, $id)
    {
        $model = CargaService::inativar(Carga::findOrFail($id));
        return new CargaResource($model->fresh(CargaService::WITH));
    }

    public function ativar(Request $request, $id)
    {
        $model = CargaService::ativar(Carga::findOrFail($id));
        return new CargaResource($model->fresh(CargaService::WITH));
    }

    /** Recalcula o extrato (idempotente) de uma safra/contrato/unidade/carga. */
    public function recalcular(Request $request)
    {
        $n = CargaService::recalcular($request->only([
            'codsafra',
            'codcarga',
            'codcontrato',
            'codunidadearmazenadora',
        ]));
        return response()->json(['recalculadas' => $n], 200);
    }
}
