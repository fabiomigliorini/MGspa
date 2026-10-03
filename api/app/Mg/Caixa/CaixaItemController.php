<?php

namespace Mg\Caixa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

/**
 * Cadastro dos itens do caixa (M13 doc-3), rotas v1/caixa-item: o
 * financeiro mantem; a listagem serve tambem aos filtros (qualquer um).
 */
class CaixaItemController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        return CaixaItemResource::collection(CaixaItemService::listar($request->only(['item', 'codfilial', 'inativo'])));
    }

    public function show(int $id)
    {
        return new CaixaItemResource(CaixaItem::with(['Filial', 'Pessoa', 'ContaContabil'])->findOrFail($id));
    }

    public function store(CaixaItemRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $item = DB::transaction(fn () => CaixaItemService::salvar(new CaixaItem(), $request->validated()));
        return new CaixaItemResource($item);
    }

    public function update(CaixaItemRequest $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        $item = CaixaItem::findOrFail($id);
        $item = DB::transaction(fn () => CaixaItemService::salvar($item, $request->validated()));
        return new CaixaItemResource($item);
    }

    public function inativar(int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        return new CaixaItemResource(CaixaItemService::inativar(CaixaItem::findOrFail($id)));
    }

    public function ativar(int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        return new CaixaItemResource(CaixaItemService::ativar(CaixaItem::findOrFail($id)));
    }

    public function destroy(int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        DB::transaction(fn () => CaixaItemService::excluir(CaixaItem::findOrFail($id)));
        return response()->json(['ok' => true]);
    }
}
