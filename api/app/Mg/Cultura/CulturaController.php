<?php

namespace Mg\Cultura;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class CulturaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = CulturaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cultura' => ['required', 'unique:tblcultura', 'min:2'],
        ]);

        $model = new Cultura();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json(Cultura::findOrFail($id), 200);
    }

    public function update(Request $request, $id)
    {
        $model = Cultura::findOrFail($id);

        $request->validate([
            'cultura' => ['required', Rule::unique('tblcultura')->ignore($model->codcultura, 'codcultura'), 'min:2'],
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        Cultura::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function resumo(Request $request, $id)
    {
        return response()->json(CulturaService::resumo($id), 200);
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(CulturaService::inativar(Cultura::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(CulturaService::ativar(Cultura::findOrFail($id)), 200);
    }
}
