<?php

namespace Mg\Classificacao;

use App\Http\Requests\Mg\Classificacao\ParametroClassificacaoStoreRequest;
use App\Http\Requests\Mg\Classificacao\ParametroClassificacaoUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ParametroClassificacaoController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        [$filter, $sort, $fields] = $this->filtros($request);
        $res = ParametroClassificacaoService::pesquisar($filter, $sort, $fields)
            ->paginate()->appends($request->all());
        return ParametroClassificacaoResource::collection($res);
    }

    public function store(ParametroClassificacaoStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = new ParametroClassificacao();
        $model->fill($request->validated());
        $model->save();

        return new ParametroClassificacaoResource($model->fresh(ParametroClassificacaoService::WITH));
    }

    public function show(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        return new ParametroClassificacaoResource(
            ParametroClassificacao::with(ParametroClassificacaoService::WITH)->findOrFail($id)
        );
    }

    public function update(ParametroClassificacaoUpdateRequest $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ParametroClassificacao::findOrFail($id);
        $model->fill($request->validated());
        $model->update();

        return new ParametroClassificacaoResource($model->fresh(ParametroClassificacaoService::WITH));
    }

    public function destroy($id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ParametroClassificacao::findOrFail($id);
        try {
            $model->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                abort(409, 'Existem cargas classificadas com este parâmetro! Impossível excluir!');
            }
            throw $e;
        }
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ParametroClassificacao::findOrFail($id);
        ParametroClassificacaoService::inativar($model);
        return new ParametroClassificacaoResource($model->fresh(ParametroClassificacaoService::WITH));
    }

    public function ativar(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ParametroClassificacao::findOrFail($id);
        ParametroClassificacaoService::ativar($model);
        return new ParametroClassificacaoResource($model->fresh(ParametroClassificacaoService::WITH));
    }
}
