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

    // Polígonos de todas as fazendas (contexto do mapa). Passe codfazenda p/
    // excluir a fazenda atual e receber só "as outras".
    public function mapa(Request $request)
    {
        $qry = Talhao::query()
            ->with('Fazenda:codfazenda,fazenda')
            ->whereNotNull('geometria')
            ->whereNull('inativo');

        if ($request->filled('codfazenda')) {
            $qry->where('codfazenda', '!=', $request->codfazenda);
        }

        $res = $qry->get(['codtalhao', 'codfazenda', 'talhao', 'geometria', 'latitude', 'longitude']);
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'talhao' => ['required', 'min:1'],
            'codfazenda' => ['required', 'exists:tblfazenda,codfazenda'],
            'area' => ['required', 'numeric', 'gt:0'],
            'geometria' => ['nullable', 'array'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'cor' => ['nullable', 'string', 'max:9'],
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
            'geometria' => ['nullable', 'array'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'cor' => ['nullable', 'string', 'max:9'],
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
