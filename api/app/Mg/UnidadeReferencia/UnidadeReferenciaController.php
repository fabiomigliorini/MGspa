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
        return response()->json($res, 200);
    }

    public function show(Request $request, $id)
    {
        return response()->json(UnidadeReferenciaService::detalhe((int) $id), 200);
    }

    public function store(Request $request)
    {
        $request->validate($this->regras());
        $model = new UnidadeReferencia();
        $model->fill($request->all());
        $model->save();
        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {
        $model = UnidadeReferencia::findOrFail($id);
        $request->validate($this->regras());
        $model->fill($request->all());
        $model->update();
        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        UnidadeReferencia::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(MgService::inativar(UnidadeReferencia::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(MgService::ativar(UnidadeReferencia::findOrFail($id)), 200);
    }

    // ---- Valores (histórico por competência) ----
    public function storeValor(Request $request, $id)
    {
        $request->validate([
            'competencia' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'gt:0'],
        ]);
        $reg = UnidadeReferenciaService::salvarValor((int) $id, $request->competencia, (float) $request->valor);
        return response()->json($reg, 201);
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
