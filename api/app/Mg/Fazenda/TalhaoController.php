<?php

namespace Mg\Fazenda;

use Illuminate\Http\Request;
use Mg\MgController;

class TalhaoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = TalhaoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'talhao' => ['required', 'min:1'],
            'codfazenda' => ['required', 'exists:tblfazenda,codfazenda'],
            'area' => ['required', 'numeric', 'gt:0'],
        ]);

        $model = new Talhao();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json(Talhao::with('Fazenda')->findOrFail($id), 200);
    }

    public function update(Request $request, $id)
    {
        $model = Talhao::findOrFail($id);

        $request->validate([
            'talhao' => ['required', 'min:1'],
            'codfazenda' => ['required', 'exists:tblfazenda,codfazenda'],
            'area' => ['required', 'numeric', 'gt:0'],
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        Talhao::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(TalhaoService::inativar(Talhao::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(TalhaoService::ativar(Talhao::findOrFail($id)), 200);
    }
}
