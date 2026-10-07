<?php

namespace Mg\Caixa;

use Carbon\Carbon;
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
        return CaixaItemResource::collection(CaixaItemService::listar($request->only(['item', 'inativo', 'modo'])));
    }

    public function show(int $id)
    {
        return new CaixaItemResource(CaixaItem::findOrFail($id));
    }

    // quanto tem do item em cada caixa (a ultima contagem de caixa fechado)
    public function saldos(int $id)
    {
        $item = CaixaItem::findOrFail($id);
        CaixaItemService::exigirCedula($item);
        return ['data' => array_map(fn ($s) => static::comDetalhe($s), CaixaItemService::saldos($item))];
    }

    // os periodos de um caixa em que o item mexeu, do mais novo para o mais
    // antigo: abertura, entradas, fechamento e diferenca; na primeira pagina,
    // em meta.totais, o total dos periodos fechados (todas as paginas)
    public function fechamentos(int $id, int $codportador)
    {
        $item = CaixaItem::findOrFail($id);
        CaixaItemService::exigirCedula($item);
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
        $item = CaixaItem::findOrFail($id);
        CaixaItemService::exigirCedula($item);
        return ['data' => CaixaItemService::descricoes($item, $request->busca)];
    }

    // os tipos do item: descricao + preco distintos
    public function tipos(int $id)
    {
        $item = CaixaItem::findOrFail($id);
        CaixaItemService::exigirCedula($item);
        return ['data' => CaixaItemService::tipos($item)];
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
        CaixaItemService::exigirCedula($item);
        $alterados = DB::transaction(fn () => CaixaItemService::renomearTipo(
            $item,
            (float) $dados['preco'],
            $dados['descricao'] ?? null,
            $dados['nova']
        ));
        return ['alterados' => $alterados, 'data' => CaixaItemService::tipos($item)];
    }

    // ==== conta corrente da maquineta de parceiro ====

    // o extrato de/ate (padrao: os ultimos 60 dias)
    public function conta(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        $request->validate([
            'de' => 'nullable|date',
            'ate' => 'nullable|date',
        ]);
        $item = CaixaItem::findOrFail($id);
        CaixaItemContaService::exigirMaquineta($item);
        $ate = $request->ate ? Carbon::parse($request->ate) : Carbon::today();
        $de = $request->de ? Carbon::parse($request->de) : $ate->copy()->subDays(60);
        return ['data' => CaixaItemContaService::extrato($item, $de, $ate)];
    }

    public function gerarTitulo(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        $dados = $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'vencimento' => 'required|date',
            'observacoes' => 'nullable|string|max:200',
        ]);
        $acerto = DB::transaction(fn () => CaixaItemContaService::gerarTitulo(
            CaixaItem::findOrFail($id),
            (float) $dados['valor'],
            Carbon::parse($dados['vencimento']),
            $dados['observacoes'] ?? null
        ));
        return ['data' => ['codcaixaitemacerto' => $acerto->codcaixaitemacerto, 'codtitulo' => $acerto->codtitulo]];
    }

    // valor com sinal: positivo aumenta o que devemos ao parceiro
    public function ajustarConta(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        $dados = $request->validate([
            'valor' => 'required|numeric|not_in:0',
            'observacoes' => 'required|string|min:3|max:300',
            'transacao' => 'nullable|date',
        ]);
        $acerto = DB::transaction(fn () => CaixaItemContaService::ajustar(
            CaixaItem::findOrFail($id),
            (float) $dados['valor'],
            $dados['observacoes'],
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null
        ));
        return ['data' => ['codcaixaitemacerto' => $acerto->codcaixaitemacerto]];
    }

    public function cancelarAcerto(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        $acerto = DB::transaction(fn () => CaixaItemContaService::cancelar(
            CaixaItemAcerto::findOrFail($id),
            $request->justificativa
        ));
        return ['data' => ['codcaixaitemacerto' => $acerto->codcaixaitemacerto]];
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
