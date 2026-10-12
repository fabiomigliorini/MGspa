<?php

namespace Mg\Grao;

use App\Http\Requests\Mg\Grao\UnidadeArmazenadoraRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class UnidadeArmazenadoraController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        [$filter, $sort, $fields] = $this->filtros($request);
        $res = UnidadeArmazenadoraService::pesquisar($filter, $sort, $fields)
            ->paginate()->appends($request->all());
        return UnidadeArmazenadoraResource::collection($res);
    }

    public function show(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        return new UnidadeArmazenadoraResource(
            UnidadeArmazenadora::with(UnidadeArmazenadoraService::WITH)->findOrFail($id)
        );
    }

    public function store(UnidadeArmazenadoraRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = UnidadeArmazenadoraService::salvar($request->validated());
        return new UnidadeArmazenadoraResource($model);
    }

    public function update(UnidadeArmazenadoraRequest $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = UnidadeArmazenadoraService::salvar(
            $request->validated(),
            UnidadeArmazenadora::findOrFail($id)
        );
        return new UnidadeArmazenadoraResource($model);
    }

    public function destroy($id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $unidade = UnidadeArmazenadora::findOrFail($id);
        try {
            $unidade->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) !== '23503') {
                throw $e;
            }
            $msg = $e->getMessage();
            abort(409, match (true) {
                str_contains($msg, 'tblcargaponto') => 'Existem cargas vinculadas a esta Unidade Armazenadora! Impossível excluir!',
                str_contains($msg, 'tblmovimentograo') => 'Existe movimentação de grão vinculada a esta Unidade Armazenadora! Impossível excluir!',
                default => 'Existem registros vinculados a esta Unidade Armazenadora! Impossível excluir!',
            });
        }
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = UnidadeArmazenadoraService::inativar(UnidadeArmazenadora::findOrFail($id));
        return new UnidadeArmazenadoraResource($model->fresh(UnidadeArmazenadoraService::WITH));
    }

    public function ativar(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = UnidadeArmazenadoraService::ativar(UnidadeArmazenadora::findOrFail($id));
        return new UnidadeArmazenadoraResource($model->fresh(UnidadeArmazenadoraService::WITH));
    }
}
