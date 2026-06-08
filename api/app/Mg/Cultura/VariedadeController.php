<?php

namespace Mg\Cultura;

use Illuminate\Http\Request;
use Mg\MgController;

class VariedadeController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = VariedadeService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'variedade' => ['required', 'min:2'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
        ]);

        $model = new Variedade();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json(Variedade::with('Cultura')->findOrFail($id), 200);
    }

    public function update(Request $request, $id)
    {
        $model = Variedade::findOrFail($id);

        $request->validate([
            'variedade' => ['required', 'min:2'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        Variedade::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(VariedadeService::inativar(Variedade::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(VariedadeService::ativar(Variedade::findOrFail($id)), 200);
    }
}
