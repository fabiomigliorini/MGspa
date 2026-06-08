<?php

namespace Mg\Fazenda;

use Illuminate\Http\Request;
use Mg\MgController;

class FazendaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = FazendaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fazenda' => ['required', 'min:2'],
        ]);

        $model = new Fazenda();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json(Fazenda::with('Pessoa')->findOrFail($id), 200);
    }

    public function resumo(Request $request, $id)
    {
        return response()->json(FazendaService::resumo($id), 200);
    }

    public function update(Request $request, $id)
    {
        $model = Fazenda::findOrFail($id);

        $request->validate([
            'fazenda' => ['required', 'min:2'],
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        Fazenda::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(FazendaService::inativar(Fazenda::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(FazendaService::ativar(Fazenda::findOrFail($id)), 200);
    }
}
