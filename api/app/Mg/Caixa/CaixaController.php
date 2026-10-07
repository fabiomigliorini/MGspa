<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Pdv\Pdv;
use Mg\Pdv\PdvRequest;
use Mg\Pdv\PdvService;
use Mg\Portador\Portador;
use Mg\Portador\PortadorLancamentoService;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\PortadorPeriodo;
use Mg\Portador\PortadorPeriodoResource;
use Mg\Portador\PortadorPeriodoService;

/**
 * Tela do caixa do PDV (MgCaixaSessao; sera' refatorada). Rotas v1/caixa
 * sobre o periodo do portador (PortadorPeriodoService). Com o codpdv da
 * gaveta, o PDV nao valida o papel nela (quem esta' na gaveta trabalha
 * nela); sem, operador do portador. O contas usa v1/portador-periodo.
 */
class CaixaController extends Controller
{
    // PDV: a gaveta do dispositivo e se o caixa esta' aberto (o wizard
    // bloqueia o Dinheiro com o caixa fechado)
    public function status(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $gaveta = $pdv->Portador;
        if (!$gaveta) {
            return ['data' => ['gaveta' => null, 'sessao' => null]];
        }
        $aberta = CaixaService::sessaoAberta($gaveta->codportador);
        return ['data' => [
            'gaveta' => ['codportador' => $gaveta->codportador, 'portador' => $gaveta->portador],
            'sessao' => $aberta ? ['codportadorperiodo' => $aberta->codportadorperiodo, 'inicio' => $aberta->inicio] : null,
        ]];
    }

    // a gaveta do PDV que pede (livre de papel); null fora do PDV
    private function livre(Request $request, int $codportador): ?int
    {
        $codpdv = $request->input('codpdv');
        if (!$codpdv) {
            return null;
        }
        $pdv = Pdv::find($codpdv);
        return $pdv && $pdv->codportador == $codportador ? $codportador : null;
    }

    private function sessao(int $id): PortadorPeriodo
    {
        $sessao = PortadorPeriodo::with('Portador')->findOrFail($id);
        if (!$this->livre(request(), $sessao->codportador)) {
            CaixaService::autorizarOperar($sessao->Portador);
        }
        return $sessao;
    }

    // a sessao e os periodos que o movimento mexeu (R14 doc-4): a propria
    // sessao e as seguintes
    private function resposta(PortadorPeriodo $sessao)
    {
        return (new SessaoResource($sessao))
            ->additional(['periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::desde($sessao))]);
    }

    private function contagem(Request $request): array
    {
        return $request->validate([
            'contagem' => 'nullable|array',
            'contagem.*' => 'integer|min:0',
            'observacoes' => 'nullable|string|max:500',
            'codpdv' => 'nullable|integer|exists:tblpdv,codpdv',
        ]);
    }

    // a gaveta: a sessao aberta ou a ultima e o envelope
    public function gaveta(int $codportador)
    {
        $gaveta = Portador::findOrFail($codportador);
        if (!$this->livre(request(), $codportador)) {
            CaixaService::autorizarOperar($gaveta);
        }
        $aberta = CaixaService::sessaoAberta($codportador);
        $sessao = $aberta ?? CaixaService::ultimaSessao($codportador);
        return ['data' => [
            'gaveta' => [
                'codportador' => $gaveta->codportador,
                'portador' => $gaveta->portador,
                'codfilial' => $gaveta->codfilial,
            ],
            'aberta' => !empty($aberta),
            'sessao' => $sessao ? new SessaoResource($sessao) : null,
            'envelope' => CaixaService::envelope($codportador),
        ]];
    }

    public function abrir(Request $request, int $codportador)
    {
        $dados = $this->contagem($request);
        $request->validate(['inicio' => 'nullable|date']);
        $gaveta = Portador::findOrFail($codportador);
        // contagem so' quando vem (o PDV conta junto; o contas conta depois)
        $sessao = DB::transaction(fn () => PortadorPeriodoService::abrir(
            $gaveta,
            $request->inicio ? Carbon::parse($request->inicio) : null,
            $dados['contagem'] ?? null,
            null,
            $dados['observacoes'] ?? null,
            $this->livre($request, $gaveta->codportador)
        ));
        return $this->resposta($sessao);
    }

    // corrige o inicio e o fim da sessao nao fechada
    public function datas(Request $request, int $id)
    {
        $dados = $request->validate([
            'inicio' => 'required|date',
            'fim' => 'nullable|date',
            'observacoes' => 'nullable|string|max:500',
        ]);
        $sessao = DB::transaction(fn () => PortadorPeriodoService::editarDatas(
            $this->sessao($id),
            Carbon::parse($dados['inicio']),
            !empty($dados['fim']) ? Carbon::parse($dados['fim']) : null,
            $dados['observacoes'] ?? null
        ));
        return $this->resposta($sessao->fresh('Portador'));
    }

    public function show(int $id)
    {
        return new SessaoResource($this->sessao($id));
    }

    public function fechar(Request $request, int $id)
    {
        $dados = $this->contagem($request);
        $request->validate(['impressora' => 'nullable|string', 'fim' => 'nullable|date']);
        $sessao = $this->sessao($id);
        $sessao = DB::transaction(fn () => PortadorPeriodoService::fecharCaixa(
            $sessao,
            $dados['contagem'] ?? null,
            null,
            $dados['observacoes'] ?? null,
            $request->fim ? Carbon::parse($request->fim) : null,
            $this->livre($request, $sessao->codportador)
        ));
        if (!empty($request->impressora)) {
            CaixaBorderoService::imprimir($sessao, $request->impressora);
        }
        return $this->resposta($sessao->fresh('Portador'));
    }

    public function reabrir(int $id)
    {
        $sessao = PortadorPeriodo::with('Portador')->findOrFail($id);
        $sessao = DB::transaction(fn () => PortadorPeriodoService::reabrirCaixa($sessao));
        return $this->resposta($sessao);
    }

    // o avulso do PDV e' ajuste (o motivo nao conta mais)
    public function avulso(Request $request, int $id)
    {
        $dados = $request->validate([
            'sentido' => 'required|in:E,S',
            'valor' => 'required|numeric|min:0.01',
            'observacoes' => 'required|string|min:3|max:300',
            'codpdv' => 'nullable|integer|exists:tblpdv,codpdv',
            'transacao' => 'nullable|date',
        ]);
        $sessao = $this->sessao($id);
        DB::transaction(fn () => PortadorLancamentoService::ajustar(
            $sessao,
            $dados['sentido'] == 'E' ? (float) $dados['valor'] : -(float) $dados['valor'],
            $dados['observacoes'],
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null,
            $this->livre($request, $sessao->codportador)
        ));
        return $this->resposta($sessao->fresh('Portador'));
    }

    public function cancelarAvulso(Request $request, int $codportadormovimento)
    {
        $mov = PortadorMovimento::findOrFail($codportadormovimento);
        $sessao = $this->sessao($mov->codportadorperiodo);
        DB::transaction(fn () => PortadorLancamentoService::cancelarAjuste(
            $mov,
            $request->input('justificativa') ?: 'Ajuste excluído no caixa',
            $this->livre($request, $sessao->codportador)
        ));
        return $this->resposta($sessao->fresh('Portador'));
    }

    // ==== bordero ====

    public function imprimirBordero(int $id, string $impressora)
    {
        CaixaBorderoService::imprimir($this->sessao($id), $impressora);
        return ['ok' => true];
    }

    // PDF do bordero: pela tela (com login) e pela impressora (rota assinada)
    public function bordero(int $id)
    {
        $sessao = PortadorPeriodo::findOrFail($id);
        return response()->make(CaixaBorderoService::pdf($sessao), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Bordero' . $sessao->codportadorperiodo . '.pdf"',
        ]);
    }

    public function borderoTela(int $id)
    {
        $this->sessao($id);
        return $this->bordero($id);
    }
}
