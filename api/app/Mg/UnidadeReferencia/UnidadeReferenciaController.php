<?php

namespace Mg\UnidadeReferencia;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;
use Mg\MgService;

class UnidadeReferenciaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = UnidadeReferenciaService::pesquisar($filter, $sort, $fields)
            ->paginate()->appends($request->all());
        return UnidadeReferenciaResource::collection($res);
    }

    public function show(Request $request, $id)
    {
        return new UnidadeReferenciaResource(UnidadeReferenciaService::detalhe((int) $id));
    }

    public function store(Request $request)
    {
        $request->validate($this->regras());
        $model = new UnidadeReferencia();
        $model->fill($request->all());
        $model->save();
        return new UnidadeReferenciaResource($this->recarregar($model));
    }

    public function update(Request $request, $id)
    {
        $model = UnidadeReferencia::findOrFail($id);
        $request->validate($this->regras());
        $model->fill($request->all());
        $model->update();
        return new UnidadeReferenciaResource($this->recarregar($model));
    }

    public function destroy($id)
    {
        UnidadeReferencia::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        $model = UnidadeReferencia::findOrFail($id);
        MgService::inativar($model);
        return new UnidadeReferenciaResource($this->recarregar($model));
    }

    public function ativar(Request $request, $id)
    {
        $model = UnidadeReferencia::findOrFail($id);
        MgService::ativar($model);
        return new UnidadeReferenciaResource($this->recarregar($model));
    }

    /** Recarrega o registro com as relações que o Resource expõe. */
    private function recarregar(UnidadeReferencia $model): UnidadeReferencia
    {
        return $model->fresh([
            'Estado',
            'Cidade',
            'UnidadeReferenciaValorS' => fn ($q) => $q->orderByDesc('competencia'),
        ]);
    }

    // ---- Valores (histórico por competência) ----
    public function storeValor(Request $request, $id)
    {
        $request->validate([
            'competencia' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'gt:0'],
        ]);
        // criarValor (NÃO upsert): 409 se a competência já existe.
        $reg = UnidadeReferenciaService::criarValor((int) $id, $request->competencia, (float) $request->valor);
        return new UnidadeReferenciaValorResource($reg);
    }

    public function updateValor(Request $request, $id, $codvalor)
    {
        $request->validate([
            'competencia' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'gt:0'],
        ]);
        $reg = UnidadeReferenciaService::atualizarValor(
            (int) $id,
            (int) $codvalor,
            $request->competencia,
            (float) $request->valor
        );
        return new UnidadeReferenciaValorResource($reg);
    }

    public function destroyValor($id, $codvalor)
    {
        UnidadeReferenciaValor::where('codunidadereferencia', $id)->findOrFail($codvalor)->delete();
        return response()->noContent();
    }

    // ---- Importar UPF-MT do site da SEFAZ-MT (best-effort) ----
    public function importarUpfMt(Request $request)
    {
        $importados = UnidadeReferenciaService::importarUpfMt();
        return response()->json([
            'importados' => $importados,
            'total' => count($importados),
        ], 200);
    }

    protected function regras(): array
    {
        return [
            'codigo' => ['required', 'max:10'],
            'descricao' => ['required', 'max:100'],
            'ente' => ['required', Rule::in(['FEDERAL', 'ESTADUAL', 'MUNICIPAL'])],
            'codestado' => ['nullable', 'exists:tblestado,codestado'],
            'codcidade' => ['nullable', 'exists:tblcidade,codcidade'],
        ];
    }
}
