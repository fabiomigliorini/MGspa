<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Caixa\CaixaService;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\TransferenciaAutorizador;
use Mg\Usuario\Autorizador;

/**
 * Periodos do portador no contas (M12 doc-3): o financeiro fecha cofre,
 * troco, banco, adquirente e cartao pela data de corte, reabre do mais novo
 * para o mais antigo e faz lancamento avulso (taxa, tarifa, rendimento,
 * ajuste/implantacao). Sessao de gaveta so' aparece (abre e fecha no PDV,
 * confere em Fechamentos). Rotas v1/portador-periodo; Financeiro/Admin.
 * Tela do portador e do periodo (doc-4): v1/portador/{cod}/periodo, para
 * quem confere a filial; o que muda movimento devolve os periodos (R14).
 */
class PortadorPeriodoController extends Controller
{
    private function autorizar(): void
    {
        Autorizador::autoriza(['Financeiro']);
    }

    public function index(Request $request)
    {
        $this->autorizar();
        $request->validate([
            'codportador' => 'nullable|integer',
            'codfilial' => 'nullable|integer',
            'estado' => 'nullable|in:aberto,fechado',
            'transacao_de' => 'nullable|date',
            'transacao_ate' => 'nullable|date',
            'gaveta' => 'nullable|boolean',
        ]);
        $q = PortadorPeriodo::query()
            ->with(['Portador.Filial:codfilial,filial', 'UsuarioFechamento:codusuario,usuario'])
            ->join('tblportador as p', 'p.codportador', '=', 'tblportadorperiodo.codportador')
            ->select('tblportadorperiodo.*');
        if ($request->codportador) {
            $q->where('tblportadorperiodo.codportador', $request->codportador);
        }
        if ($request->codfilial) {
            $q->where('p.codfilial', $request->codfilial);
        }
        if ($request->estado == 'aberto') {
            $q->whereNull('tblportadorperiodo.fechamento');
        } elseif ($request->estado == 'fechado') {
            $q->whereNotNull('tblportadorperiodo.fechamento');
        }
        // periodos que encostam no intervalo
        if ($request->transacao_ate) {
            $q->where('tblportadorperiodo.inicio', '<=', Carbon::parse($request->transacao_ate)->endOfDay());
        }
        if ($request->transacao_de) {
            $de = Carbon::parse($request->transacao_de)->startOfDay();
            $q->where(fn ($w) => $w->whereNull('tblportadorperiodo.fim')->orWhere('tblportadorperiodo.fim', '>=', $de));
        }
        if ($request->has('gaveta') && !$request->boolean('gaveta')) {
            $q->whereNotExists(fn ($e) => $e->selectRaw('1')->from('tblpdv as d')->whereColumn('d.codportador', 'p.codportador'));
        }
        $q->orderBy('p.portador')->orderBy('tblportadorperiodo.inicio', 'desc');
        return PortadorPeriodoResource::collection($q->paginate(50));
    }

    public function show(int $id)
    {
        $this->autorizar();
        return (new PortadorPeriodoResource(PortadorPeriodo::findOrFail($id)))->comLancamentos();
    }

    // tela /portador/{cod}/{codperiodo} (doc-4): o portador, as abas (todos
    // os periodos, sem lancamentos) e o periodo escolhido (sem ele, o
    // ultimo). Gerente ve as filiais dele; Financeiro e Admin, todas (R15)
    public function tela(int $codportador, ?int $codportadorperiodo = null)
    {
        $portador = Portador::with(['Banco', 'Filial'])->findOrFail($codportador);
        ConferenciaAutorizador::autorizar($portador->codfilial);
        $periodos = PortadorPeriodo::where('codportador', $codportador)
            ->with(['UsuarioAbertura:codusuario,usuario', 'UsuarioFechamento:codusuario,usuario'])
            ->orderBy('inicio')
            ->orderBy('codportadorperiodo')
            ->get()
            ->each(fn ($p) => $p->setRelation('Portador', $portador));
        $periodo = $codportadorperiodo ? $periodos->firstWhere('codportadorperiodo', $codportadorperiodo) : $periodos->last();
        if ($codportadorperiodo && !$periodo) {
            abort(404, 'Período não é deste portador.');
        }
        $caixa = $portador->ehCaixa();
        $operar = $caixa && CaixaService::podeOperar($portador);
        $financeiro = Autorizador::pode(['Financeiro']);
        return ['data' => [
            'portador' => new PortadorResource($portador),
            'pode' => [
                'cadastro' => $financeiro,
                'transferir' => TransferenciaAutorizador::podeOperar($portador),
                'avulso' => $caixa ? $operar : $financeiro,
                'caixa' => $operar,
                'reabrirCaixa' => $caixa && ConferenciaAutorizador::pode($portador->codfilial),
                'periodo' => !$caixa && $financeiro,
            ],
            'periodos' => PortadorPeriodoResource::collection($periodos),
            'periodo' => $periodo ? (new PortadorPeriodoResource($periodo))->comLancamentos() : null,
        ]];
    }

    public function fechar(Request $request, int $id)
    {
        $this->autorizar();
        $request->validate(['corte' => 'nullable|date']);
        $periodo = DB::transaction(fn () => PortadorPeriodoService::fechar(
            PortadorPeriodo::findOrFail($id),
            $request->corte ? Carbon::parse($request->corte) : null
        ));
        return (new PortadorPeriodoResource($periodo->fresh()))->comLancamentos()
            ->additional(['periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::desde($periodo))]);
    }

    public function reabrir(int $id)
    {
        $this->autorizar();
        $periodo = DB::transaction(fn () => PortadorPeriodoService::reabrir(PortadorPeriodo::findOrFail($id)));
        return (new PortadorPeriodoResource($periodo->fresh()))->comLancamentos()
            ->additional(['periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::desde($periodo))]);
    }

    // contagem do caixa (especie) inicial ou final da sessao
    public function contagem(Request $request, int $id)
    {
        $dados = $request->validate([
            'momento' => 'required|in:inicial,final',
            'contagem' => 'nullable|array',
            'contagem.*' => 'nullable|integer|min:0',
            'itens' => 'nullable|array',
            'itens.*' => 'nullable|numeric|min:0',
        ]);
        $periodo = PortadorPeriodo::with('Portador')->findOrFail($id);
        CaixaService::autorizarOperar($periodo->Portador);
        $periodo = DB::transaction(fn () => PortadorPeriodoService::contar(
            $periodo,
            $dados['momento'],
            $dados['contagem'] ?? [],
            $dados['itens'] ?? []
        ));
        return (new PortadorPeriodoResource($periodo->fresh()))->comLancamentos()
            ->additional(['periodos' => PortadorPeriodoResource::lista(collect([$periodo]))]);
    }

    public function lancamento(Request $request)
    {
        $this->autorizar();
        $dados = $request->validate([
            'codportador' => 'required|integer|exists:tblportador,codportador',
            'motivo' => 'required|in:' . implode(',', array_keys(PagamentoService::MOTIVOS)),
            'valor' => 'required|numeric|not_in:0',
            'transacao' => 'nullable|date',
            'observacoes' => 'nullable|string|max:300',
        ]);
        $pag = DB::transaction(fn () => PortadorPeriodoService::lancar(
            Portador::findOrFail($dados['codportador']),
            $dados['motivo'],
            (float) $dados['valor'],
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null,
            $dados['observacoes'] ?? null
        ));
        return $this->pagamento($pag);
    }

    public function cancelarLancamento(Request $request, int $codpagamento)
    {
        $this->autorizar();
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        $pag = DB::transaction(fn () => PortadorPeriodoService::cancelarLancamento(
            Pagamento::findOrFail($codpagamento),
            $request->justificativa
        ));
        return $this->pagamento($pag);
    }

    // o pagamento e os periodos que ele mexeu (R14)
    private function pagamento(Pagamento $pag)
    {
        return (new PagamentoDetalheResource(PagamentoListaService::carregar($pag->codpagamento)))
            ->additional(['periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::afetados($pag->codpagamento))]);
    }
}
