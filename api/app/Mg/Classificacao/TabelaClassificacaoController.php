<?php

namespace Mg\Classificacao;

use App\Http\Requests\Mg\Classificacao\TabelaClassificacaoStoreRequest;
use App\Http\Requests\Mg\Classificacao\TabelaClassificacaoUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Mg\MgController;

class TabelaClassificacaoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = TabelaClassificacaoService::pesquisar($filter, $sort, $fields)
            ->paginate()->appends($request->all());
        return TabelaClassificacaoResource::collection($res);
    }

    public function store(TabelaClassificacaoStoreRequest $request)
    {
        $model = TabelaClassificacaoService::salvar($request->validated());
        return new TabelaClassificacaoResource($model);
    }

    public function show(Request $request, $id)
    {
        return new TabelaClassificacaoResource(
            TabelaClassificacao::with(TabelaClassificacaoService::WITH)->findOrFail($id)
        );
    }

    public function update(TabelaClassificacaoUpdateRequest $request, $id)
    {
        $model = TabelaClassificacaoService::salvar($request->validated(), TabelaClassificacao::findOrFail($id));
        return new TabelaClassificacaoResource($model);
    }

    public function destroy($id)
    {
        $model = TabelaClassificacao::findOrFail($id);
        try {
            $model->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                abort(409, 'Existem cargas ou cadastros usando esta tabela! Impossível excluir!');
            }
            throw $e;
        }
        return response()->noContent();
    }

    /** Marca esta tabela como a padrão da cultura. */
    public function padrao(Request $request, $id)
    {
        $model = TabelaClassificacao::findOrFail($id);
        TabelaClassificacaoService::marcarPadrao($model);
        return new TabelaClassificacaoResource($model->fresh(TabelaClassificacaoService::WITH));
    }

    public function inativar(Request $request, $id)
    {
        $model = TabelaClassificacao::findOrFail($id);
        TabelaClassificacaoService::inativar($model);
        return new TabelaClassificacaoResource($model->fresh(TabelaClassificacaoService::WITH));
    }

    public function ativar(Request $request, $id)
    {
        $model = TabelaClassificacao::findOrFail($id);
        TabelaClassificacaoService::ativar($model);
        return new TabelaClassificacaoResource($model->fresh(TabelaClassificacaoService::WITH));
    }
}
