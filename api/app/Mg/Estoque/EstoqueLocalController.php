<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class EstoqueLocalController extends MgController
{
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $qry = EstoqueLocalService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        // sem fields (o cadastro do app estoque): com filial e impressora; com fields
        // (selects antigos do quasar) fica so' o que foi pedido
        if (empty($fields)) {
            $res->getCollection()->load(['Filial', 'Impressora']);
            $res->setCollection($res->getCollection()->map(fn ($l) => EstoqueLocalService::detalhar($l)));
        }
        return response()->json($res, 206);
    }

    public function show(Request $request, $id)
    {
        $model = EstoqueLocal::with(['Filial', 'Impressora'])->findOrFail($id);
        return response()->json(EstoqueLocalService::detalhar($model), 200);
    }

    // Cadastro (TASK-55, etapa 2): so' Administrador grava
    public function store(EstoqueLocalRequest $request)
    {
        Autorizador::autoriza([]);
        $model = DB::transaction(fn () => EstoqueLocalService::salvar($request->validated()));
        return response()->json(EstoqueLocalService::detalhar($model->fresh(['Filial', 'Impressora'])), 201);
    }

    public function update(EstoqueLocalRequest $request, $id)
    {
        Autorizador::autoriza([]);
        $model = EstoqueLocal::findOrFail($id);
        $model = DB::transaction(fn () => EstoqueLocalService::salvar($request->validated(), $model));
        return response()->json(EstoqueLocalService::detalhar($model->fresh(['Filial', 'Impressora'])), 200);
    }

    public function inativar($id)
    {
        Autorizador::autoriza([]);
        $model = DB::transaction(fn () => EstoqueLocalService::inativar(EstoqueLocal::findOrFail($id)));
        return response()->json(EstoqueLocalService::detalhar($model->fresh(['Filial', 'Impressora'])), 200);
    }

    public function ativar($id)
    {
        Autorizador::autoriza([]);
        $model = DB::transaction(fn () => EstoqueLocalService::ativar(EstoqueLocal::findOrFail($id)));
        return response()->json(EstoqueLocalService::detalhar($model->fresh(['Filial', 'Impressora'])), 200);
    }

    public function autocompletar(Request $request)
    {
        $res = EstoqueLocalService::autocompletar($request->all());
        return response()->json($res, 206);
    }
}
