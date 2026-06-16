<?php

namespace Mg\Safra;

use App\Http\Requests\Mg\Safra\CargaColheitaSincronizarRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class CargaColheitaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = CargaColheitaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return CargaColheitaResource::collection($res);
    }

    public function show(Request $request, $id)
    {
        return new CargaColheitaResource(CargaColheita::with(CargaColheitaService::WITH)->findOrFail($id));
    }

    /**
     * Sincroniza uma carga criada/editada offline (upsert por uuid). Aceita
     * carga parcial — pesos/classificacao chegam ao longo das etapas do patio.
     */
    public function sincronizar(CargaColheitaSincronizarRequest $request)
    {
        // O service é a autoridade (recalcula pesos/descontos) e aceita carga
        // parcial ao longo das etapas — passa o payload completo, não só o
        // subconjunto validado, igual ao Embarque::sincronizar.
        $request->validated();
        $carga = CargaColheitaService::sincronizar($request->all());

        return new CargaColheitaResource($carga->load(CargaColheitaService::WITH));
    }

    public function inativar(Request $request, $id)
    {
        CargaColheitaService::inativar(CargaColheita::findOrFail($id));
        return new CargaColheitaResource(CargaColheita::with(CargaColheitaService::WITH)->findOrFail($id));
    }

    public function ativar(Request $request, $id)
    {
        CargaColheitaService::ativar(CargaColheita::findOrFail($id));
        return new CargaColheitaResource(CargaColheita::with(CargaColheitaService::WITH)->findOrFail($id));
    }
}
