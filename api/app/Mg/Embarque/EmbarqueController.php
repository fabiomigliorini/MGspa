<?php

namespace Mg\Embarque;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class EmbarqueController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = EmbarqueService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function show(Request $request, $id)
    {
        return response()->json(Embarque::with(EmbarqueService::WITH)->findOrFail($id), 200);
    }

    /**
     * Sincroniza um embarque criado/editado offline (upsert por uuid). Aceita
     * parcial — pesos/classificacao/NF chegam ao longo das etapas do patio.
     */
    public function sincronizar(Request $request)
    {
        $request->validate([
            'uuid' => ['required', 'string'],
            'etapa' => ['required', Rule::in(EmbarqueService::ETAPAS)],
            'data' => ['required', 'date'],
            'contratos' => ['array'],
            'contratos.*.codcontrato' => ['required', 'exists:tblcontrato,codcontrato'],
            'contratos.*.quantidade' => ['nullable', 'numeric'],
            'origens' => ['array'],
            'origens.*.tipo' => ['required', Rule::in(['SILO', 'TALHAO'])],
            'origens.*.codplantio' => ['nullable', 'exists:tblplantio,codplantio'],
            'pesotara' => ['nullable', 'numeric', 'gte:0'],
            'pesobruto' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $embarque = EmbarqueService::sincronizar($request->all());

        return response()->json($embarque, 200);
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(EmbarqueService::inativar(Embarque::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(EmbarqueService::ativar(Embarque::findOrFail($id)), 200);
    }
}
