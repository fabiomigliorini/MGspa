<?php

namespace Mg\Fazenda;

use App\Http\Requests\Mg\Fazenda\TalhaoStoreRequest;
use App\Http\Requests\Mg\Fazenda\TalhaoUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class TalhaoController extends MgController
{
    const WITH = ['Fazenda'];

    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = TalhaoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return TalhaoResource::collection($res);
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
        return response()->json($res->map(function ($t) {
            return [
                'codtalhao' => (int) $t->codtalhao,
                'codfazenda' => (int) $t->codfazenda,
                'talhao' => $t->talhao,
                'geometria' => $t->geometria,
                'latitude' => $t->latitude,
                'longitude' => $t->longitude,
                'Fazenda' => $t->Fazenda,
            ];
        }), 200);
    }

    public function store(TalhaoStoreRequest $request)
    {
        $model = new Talhao();
        $model->fill($request->validated());
        $model->save();

        return new TalhaoResource($model->fresh(static::WITH));
    }

    public function show(Request $request, $id)
    {
        return new TalhaoResource(Talhao::with(static::WITH)->findOrFail($id));
    }

    public function update(TalhaoUpdateRequest $request, $id)
    {
        $model = Talhao::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new TalhaoResource($model->fresh(static::WITH));
    }

    public function destroy($id)
    {
        Talhao::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        $model = Talhao::findOrFail($id);
        TalhaoService::inativar($model);
        return new TalhaoResource($model->fresh(static::WITH));
    }

    public function ativar(Request $request, $id)
    {
        $model = Talhao::findOrFail($id);
        TalhaoService::ativar($model);
        return new TalhaoResource($model->fresh(static::WITH));
    }
}
