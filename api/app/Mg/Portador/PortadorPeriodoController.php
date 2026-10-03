<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Usuario\Autorizador;

/**
 * Periodos do portador no contas (M12 doc-3): o financeiro fecha cofre,
 * troco, banco, adquirente e cartao pela data de corte, reabre do mais novo
 * para o mais antigo e faz lancamento avulso (taxa, tarifa, rendimento,
 * ajuste/implantacao). Sessao de gaveta so' aparece (abre e fecha no PDV,
 * confere em Fechamentos). Rotas v1/portador-periodo; Financeiro/Admin.
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

    public function fechar(Request $request, int $id)
    {
        $this->autorizar();
        $request->validate(['corte' => 'nullable|date']);
        $periodo = DB::transaction(fn () => PortadorPeriodoService::fechar(
            PortadorPeriodo::findOrFail($id),
            $request->corte ? Carbon::parse($request->corte) : null
        ));
        return (new PortadorPeriodoResource($periodo->fresh()))->comLancamentos();
    }

    public function reabrir(int $id)
    {
        $this->autorizar();
        $periodo = DB::transaction(fn () => PortadorPeriodoService::reabrir(PortadorPeriodo::findOrFail($id)));
        return (new PortadorPeriodoResource($periodo->fresh()))->comLancamentos();
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
        return new PagamentoDetalheResource(PagamentoListaService::carregar($pag->codpagamento));
    }
}
