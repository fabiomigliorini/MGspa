<?php

namespace Mg\Classificacao;

use App\Http\Requests\Mg\Classificacao\ParametroClassificacaoStoreRequest;
use App\Http\Requests\Mg\Classificacao\ParametroClassificacaoUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Mg\MgController;

class ParametroClassificacaoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = ParametroClassificacaoService::pesquisar($filter, $sort, $fields)
            ->paginate()->appends($request->all());
        return ParametroClassificacaoResource::collection($res);
    }

    public function store(ParametroClassificacaoStoreRequest $request)
    {
        $model = new ParametroClassificacao();
        $model->fill($request->validated());
        $model->save();

        return new ParametroClassificacaoResource($model->fresh());
    }

    public function show(Request $request, $id)
    {
        return new ParametroClassificacaoResource(ParametroClassificacao::findOrFail($id));
    }

    public function update(ParametroClassificacaoUpdateRequest $request, $id)
    {
        $model = ParametroClassificacao::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new ParametroClassificacaoResource($model->fresh());
    }

    public function destroy($id)
    {
        $model = ParametroClassificacao::findOrFail($id);
        try {
            $model->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                abort(409, 'Existem tabelas ou classificações usando este parâmetro! Impossível excluir!');
            }
            throw $e;
        }
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        $model = ParametroClassificacao::findOrFail($id);
        ParametroClassificacaoService::inativar($model);
        return new ParametroClassificacaoResource($model->fresh());
    }

    public function ativar(Request $request, $id)
    {
        $model = ParametroClassificacao::findOrFail($id);
        ParametroClassificacaoService::ativar($model);
        return new ParametroClassificacaoResource($model->fresh());
    }
}
