<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Caixa\CaixaBorderoService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Usuario\Autorizador;

/**
 * O portador e o periodo no contas (doc-4, redefinicao do dinheiro): a tela
 * /portador/{cod}/{codperiodo} e o que muda o periodo (especie: abrir,
 * contar, fechar, reabrir, datas, dividir, unificar; banco: fechar com corte,
 * reabrir, taxa/tarifa/rendimento). Quem pode: o papel no portador
 * (PortadorAutorizador). O que muda devolve os periodos afetados (R14).
 * O caixa do PDV usa a mesma tela, so' com o periodo aberto da gaveta: com o
 * codpdv dela (livre), ve, abre, conta e imprime o bordero sem papel.
 */
class PortadorPeriodoController extends Controller
{
    private function periodo(int $id): PortadorPeriodo
    {
        return PortadorPeriodo::with('Portador')->findOrFail($id);
    }

    // o periodo e os periodos dele em diante
    private function resposta(PortadorPeriodo $periodo)
    {
        return (new PortadorPeriodoResource($periodo->fresh()))->comLancamentos()
            ->additional(['periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::desde($periodo))]);
    }

    // tela /portador/{cod}/{codperiodo}: o portador, as abas (todos os
    // periodos, sem lancamentos) e o periodo escolhido (sem ele, o ultimo).
    // So' operador ou gestor do portador; no PDV, a gaveta dele (sem as acoes
    // de gestor)
    public function tela(int $codportador, ?int $codportadorperiodo = null)
    {
        $portador = Portador::with(['Banco', 'Filial'])->findOrFail($codportador);
        $pdv = PortadorAutorizador::livre() == $codportador;
        if (!$pdv) {
            PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Ver');
        }
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
        $gestor = !$pdv && PortadorAutorizador::pode($codportador, PortadorUsuario::PAPEL_GESTOR);
        return ['data' => [
            'portador' => new PortadorResource($portador),
            'papel' => $pdv ? PortadorUsuario::PAPEL_OPERADOR : PortadorAutorizador::papel($codportador),
            'pode' => [
                'cadastro' => !$pdv && Autorizador::pode(['Financeiro']),
                'operar' => true,
                'gestor' => $gestor,
                'usuarios' => $gestor,
            ],
            'periodos' => PortadorPeriodoResource::collection($periodos),
            'periodo' => $periodo ? (new PortadorPeriodoResource($periodo))->comLancamentos() : null,
        ]];
    }

    // ==== especie ====

    // cedulas e moedas {face: quantidade} e os itens do caixa
    // {codcaixaitem: [{preco, quantidade, descricao}]}
    private function contagem(Request $request): array
    {
        return $request->validate([
            'contagem' => 'nullable|array',
            'contagem.*' => 'nullable|integer|min:0',
            'itens' => 'nullable|array',
            'itens.*' => 'nullable|array',
            'itens.*.*.preco' => 'nullable|numeric|min:0',
            'itens.*.*.quantidade' => 'nullable|integer|min:0',
            'itens.*.*.descricao' => 'nullable|string|max:50',
            'observacoes' => 'nullable|string|max:500',
        ]);
    }

    // itens nao enviados = mantem os de antes
    private function itens(Request $request, array $dados): ?array
    {
        return $request->has('itens') ? ($dados['itens'] ?? []) : null;
    }

    // sem inicio, o servidor decide: o segundo seguinte ao fim do anterior
    // (sem periodo, o comeco de hoje)
    public function abrir(Request $request, int $codportador)
    {
        $dados = $this->contagem($request);
        $request->validate(['inicio' => 'nullable|date']);
        $portador = Portador::findOrFail($codportador);
        $periodo = DB::transaction(fn () => PortadorPeriodoService::abrir(
            $portador,
            $request->inicio ? Carbon::parse($request->inicio) : PortadorPeriodoService::inicioDoNovo($portador),
            $request->has('contagem') ? ($dados['contagem'] ?? []) : null,
            $this->itens($request, $dados),
            $dados['observacoes'] ?? null,
            PortadorAutorizador::livre()
        ));
        return $this->resposta($periodo);
    }

    public function contar(Request $request, int $id)
    {
        $dados = $this->contagem($request);
        $request->validate(['momento' => 'required|in:inicial,final']);
        $periodo = DB::transaction(fn () => PortadorPeriodoService::contar(
            $this->periodo($id),
            $request->momento,
            $dados['contagem'] ?? null,
            $this->itens($request, $dados),
            PortadorAutorizador::livre()
        ));
        return $this->resposta($periodo);
    }

    // fecha (especie, com a contagem final) ou fecha com corte (banco)
    public function fechar(Request $request, int $id)
    {
        $periodo = $this->periodo($id);
        if (!$periodo->Portador->ehCaixa()) {
            $request->validate(['corte' => 'nullable|date']);
            $periodo = DB::transaction(fn () => PortadorPeriodoService::fechar(
                $periodo,
                $request->corte ? Carbon::parse($request->corte) : null
            ));
            return $this->resposta($periodo);
        }
        $dados = $this->contagem($request);
        $request->validate(['fim' => 'nullable|date']);
        $periodo = DB::transaction(fn () => PortadorPeriodoService::fecharCaixa(
            $periodo,
            $request->has('contagem') ? ($dados['contagem'] ?? []) : null,
            $this->itens($request, $dados),
            $dados['observacoes'] ?? null,
            $request->fim ? Carbon::parse($request->fim) : null
        ));
        return $this->resposta($periodo);
    }

    public function reabrir(int $id)
    {
        $periodo = $this->periodo($id);
        $periodo = DB::transaction(fn () => $periodo->Portador->ehCaixa()
            ? PortadorPeriodoService::reabrirCaixa($periodo)
            : PortadorPeriodoService::reabrir($periodo));
        return $this->resposta($periodo);
    }

    public function datas(Request $request, int $id)
    {
        $dados = $request->validate([
            'inicio' => 'required|date',
            'fim' => 'nullable|date',
            'observacoes' => 'nullable|string|max:500',
        ]);
        $periodo = DB::transaction(fn () => PortadorPeriodoService::editarDatas(
            $this->periodo($id),
            Carbon::parse($dados['inicio']),
            !empty($dados['fim']) ? Carbon::parse($dados['fim']) : null,
            $dados['observacoes'] ?? null
        ));
        return $this->resposta($periodo);
    }

    // devolve a segunda parte (a tela vai para ela)
    public function dividir(Request $request, int $id)
    {
        $request->validate(['corte' => 'required|date']);
        $periodo = $this->periodo($id);
        $segunda = DB::transaction(fn () => PortadorPeriodoService::dividir($periodo, Carbon::parse($request->corte)));
        return (new PortadorPeriodoResource($segunda))->comLancamentos()
            ->additional(['periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::desde($periodo))]);
    }

    // junta ao anterior; devolve o anterior (a tela vai para ele) e a lista
    // completa (o periodo unido some)
    public function unificar(int $id)
    {
        $anterior = DB::transaction(fn () => PortadorPeriodoService::unificar($this->periodo($id)));
        return (new PortadorPeriodoResource($anterior))->comLancamentos()
            ->additional(['periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::desde($anterior)), 'removido' => $id]);
    }

    // o bordero de quem ve o periodo (o caixa do PDV, a gaveta dele)
    private function periodoDoBordero(int $id): PortadorPeriodo
    {
        $periodo = $this->periodo($id);
        if (PortadorAutorizador::livre() != $periodo->codportador) {
            PortadorAutorizador::autorizar($periodo->Portador, PortadorUsuario::PAPEL_OPERADOR, 'Ver');
        }
        return $periodo;
    }

    public function bordero(int $id)
    {
        $periodo = $this->periodoDoBordero($id);
        return response()->make(CaixaBorderoService::pdf($periodo), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Bordero' . $periodo->codportadorperiodo . '.pdf"',
        ]);
    }

    // na termica do PDV (rota assinada do bordero, CaixaBorderoService)
    public function imprimirBordero(int $id, string $impressora)
    {
        CaixaBorderoService::imprimir($this->periodoDoBordero($id), $impressora);
        return ['ok' => true];
    }

    // ==== banco: taxa, tarifa, rendimento ====

    public function lancamento(Request $request)
    {
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
