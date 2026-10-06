<?php

namespace Mg\Caixa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Portador\PortadorPeriodoResource;
use Mg\Usuario\Autorizador;

/**
 * Cadastro dos itens do caixa (doc-4, "Itens do caixa"), rotas v1/caixa-item: o
 * financeiro mantem; a listagem serve tambem aos filtros (qualquer um).
 */
class CaixaItemController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        return CaixaItemResource::collection(CaixaItemService::listar($request->only(['item', 'inativo'])));
    }

    public function show(int $id)
    {
        return new CaixaItemResource(CaixaItem::findOrFail($id));
    }

    // quanto tem do item em cada caixa (a ultima contagem de caixa fechado)
    public function saldos(int $id)
    {
        $item = CaixaItem::findOrFail($id);
        return ['data' => array_map(fn ($s) => static::comDetalhe($s), CaixaItemService::saldos($item))];
    }

    // os periodos de um caixa em que o item mexeu, do mais novo para o mais
    // antigo: abertura, entradas, fechamento e diferenca; na primeira pagina,
    // em meta.totais, o total dos periodos fechados (todas as paginas)
    public function fechamentos(int $id, int $codportador)
    {
        $item = CaixaItem::findOrFail($id);
        $pagina = CaixaItemService::fechamentos($item, $codportador)->through(fn ($f) => array_merge($f, [
            'abertura' => static::comDetalhe($f['abertura']),
            'fechamento' => $f['fechamento'] ? static::comDetalhe($f['fechamento']) : null,
            'lancamentos' => array_map(fn ($l) => static::comDetalhe($l), $f['lancamentos']),
        ]));
        return [
            'data' => $pagina->items(),
            'meta' => [
                'current_page' => $pagina->currentPage(),
                'last_page' => $pagina->lastPage(),
                'totais' => $pagina->currentPage() == 1
                    ? CaixaItemService::totaisFechamentos($item, $codportador)
                    : null,
            ],
        ];
    }

    // as descricoes ja' usadas no item (entradas e contagens), para o
    // typeahead da entrada
    public function descricoes(Request $request, int $id)
    {
        $request->validate(['busca' => 'nullable|string|max:50']);
        return ['data' => CaixaItemService::descricoes(CaixaItem::findOrFail($id), $request->busca)];
    }

    // os tipos do item: descricao + preco distintos
    public function tipos(int $id)
    {
        return ['data' => CaixaItemService::tipos(CaixaItem::findOrFail($id))];
    }

    // troca a descricao de um tipo (descricao + preco) em tudo
    public function renomearTipo(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        $dados = $request->validate([
            'preco' => 'required|numeric|min:0.01',
            'descricao' => 'nullable|string|max:50',
            'nova' => 'required|string|max:50',
        ]);
        $item = CaixaItem::findOrFail($id);
        $alterados = DB::transaction(fn () => CaixaItemService::renomearTipo(
            $item,
            (float) $dados['preco'],
            $dados['descricao'] ?? null,
            $dados['nova']
        ));
        return ['alterados' => $alterados, 'data' => CaixaItemService::tipos($item)];
    }

    // "1 × 25,00 Claro · 2 × 30,00 Oi"
    private static function comDetalhe(array $r): array
    {
        return $r + ['detalhe' => PortadorPeriodoResource::textoLinhas($r['linhas'])];
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
