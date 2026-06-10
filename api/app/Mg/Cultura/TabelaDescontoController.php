<?php

namespace Mg\Cultura;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class TabelaDescontoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = TabelaDescontoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate($this->regras());

        $model = new TabelaDesconto();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json(TabelaDesconto::with('Cultura')->findOrFail($id), 200);
    }

    public function update(Request $request, $id)
    {
        $model = TabelaDesconto::findOrFail($id);

        $request->validate($this->regras());

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        TabelaDesconto::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(TabelaDescontoService::inativar(TabelaDesconto::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(TabelaDescontoService::ativar(TabelaDesconto::findOrFail($id)), 200);
    }

    protected function regras(): array
    {
        return [
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'tipo' => ['required', Rule::in(['UMIDADE', 'IMPUREZA', 'AVARIADOS', 'ESVERDEADOS', 'QUEBRADOS'])],
            'faixainicio' => ['required', 'numeric'],
            'faixafim' => ['required', 'numeric', 'gte:faixainicio'],
            'percentualdesconto' => ['required', 'numeric', 'gte:0'],
        ];
    }
}
