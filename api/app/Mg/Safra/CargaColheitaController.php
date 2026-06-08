<?php

namespace Mg\Safra;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class CargaColheitaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = CargaColheitaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function show(Request $request, $id)
    {
        return response()->json(CargaColheita::with(CargaColheitaService::WITH)->findOrFail($id), 200);
    }

    /**
     * Sincroniza uma carga criada/editada offline (upsert por uuid). Aceita
     * carga parcial — pesos/classificacao chegam ao longo das etapas do patio.
     */
    public function sincronizar(Request $request)
    {
        $request->validate([
            'uuid' => ['required', 'string'],
            'codsafra' => ['required', 'exists:tblsafra,codsafra'],
            'etapa' => ['required', Rule::in(CargaColheitaService::ETAPAS)],
            'data' => ['required', 'date'],
            'plantios' => ['array'],
            'plantios.*.codplantio' => ['required', 'exists:tblplantio,codplantio'],
            'plantios.*.percentual' => ['nullable', 'numeric', 'gte:0'],
            'pesobruto' => ['nullable', 'numeric', 'gte:0'],
            'tara' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $carga = CargaColheitaService::sincronizar($request->all());

        return response()->json($carga, 200);
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(CargaColheitaService::inativar(CargaColheita::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(CargaColheitaService::ativar(CargaColheita::findOrFail($id)), 200);
    }
}
