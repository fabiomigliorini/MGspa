<?php

namespace Mg\Caixa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Pdv\PdvRequest;
use Mg\Pdv\PdvService;
use Mg\Portador\Portador;
use Mg\Portador\PortadorPeriodo;
use Mg\Portador\PortadorPeriodoResource;
use Mg\Portador\PortadorPeriodoService;

/**
 * Tela do caixa (M9; numa tela so' desde o M13 doc-3): a mesma no PDV e no
 * contas. Rotas v1/caixa (o usuario autoriza: Caixa ou Gerente da filial,
 * Financeiro, Administrador). O PDV so' pergunta qual e' a gaveta dele
 * (v1/pdv/caixa) e imprime o bordero.
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

    private function sessao(int $id): PortadorPeriodo
    {
        $sessao = PortadorPeriodo::with('Portador')->findOrFail($id);
        CaixaService::autorizarOperar($sessao->Portador->codfilial);
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
            'itens' => 'nullable|array',
            'itens.*' => 'numeric|min:0',
            'observacoes' => 'nullable|string|max:250',
            'codpdv' => 'nullable|integer|exists:tblpdv,codpdv',
        ]);
    }

    // a gaveta: a sessao aberta ou a ultima, os itens para abrir e o
    // envelope
    public function gaveta(int $codportador)
    {
        $gaveta = Portador::findOrFail($codportador);
        CaixaService::autorizarOperar($gaveta->codfilial);
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
            'itens' => CaixaItemResource::collection(CaixaItemService::ativosDaFilial($gaveta->codfilial)),
        ]];
    }

    public function abrir(Request $request, int $codportador)
    {
        $dados = $this->contagem($request);
        $gaveta = Portador::findOrFail($codportador);
        $sessao = DB::transaction(fn () => CaixaService::abrir(
            $gaveta,
            $dados['contagem'] ?? [],
            $dados['itens'] ?? [],
            $dados['observacoes'] ?? null,
            $dados['codpdv'] ?? null
        ));
        return $this->resposta($sessao);
    }

    public function show(int $id)
    {
        return new SessaoResource($this->sessao($id));
    }

    public function fechar(Request $request, int $id)
    {
        $dados = $this->contagem($request);
        $request->validate(['impressora' => 'nullable|string']);
        $sessao = $this->sessao($id);
        $sessao = DB::transaction(fn () => CaixaService::fechar(
            $sessao,
            $dados['contagem'] ?? [],
            $dados['itens'] ?? [],
            $dados['observacoes'] ?? null,
            $dados['codpdv'] ?? null
        ));
        if (!empty($request->impressora)) {
            CaixaBorderoService::imprimir($sessao, $request->impressora);
        }
        return $this->resposta($sessao->fresh('Portador'));
    }

    public function reabrir(int $id)
    {
        $sessao = PortadorPeriodo::with('Portador')->findOrFail($id);
        $sessao = DB::transaction(fn () => CaixaService::reabrir($sessao));
        return $this->resposta($sessao);
    }

    public function salvarItem(Request $request, int $id, int $codcaixaitem)
    {
        $dados = $request->validate([
            'valorentrada' => 'nullable|numeric|min:0',
            'valorsaida' => 'nullable|numeric|min:0',
            'valorvendido' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string|max:300',
            'codpdv' => 'nullable|integer|exists:tblpdv,codpdv',
        ]);
        $sessao = $this->sessao($id);
        $item = CaixaItem::findOrFail($codcaixaitem);
        DB::transaction(fn () => CaixaService::salvarItem($sessao, $item, $dados, $dados['codpdv'] ?? null));
        return $this->resposta($sessao->fresh('Portador'));
    }

    public function avulso(Request $request, int $id)
    {
        $dados = $request->validate([
            'sentido' => 'required|in:E,S',
            'motivo' => 'required|in:' . implode(',', array_keys(PagamentoService::MOTIVOS)),
            'valor' => 'required|numeric|min:0.01',
            'observacoes' => 'required|string|min:3|max:300',
            'codpdv' => 'nullable|integer|exists:tblpdv,codpdv',
        ]);
        $sessao = $this->sessao($id);
        DB::transaction(fn () => CaixaService::lancarAvulso(
            $sessao,
            $dados['sentido'],
            $dados['motivo'],
            (float) $dados['valor'],
            $dados['observacoes'],
            $dados['codpdv'] ?? null
        ));
        return $this->resposta($sessao->fresh('Portador'));
    }

    public function cancelarAvulso(int $codpagamento)
    {
        $pag = Pagamento::findOrFail($codpagamento);
        $sessao = $this->sessao((int) $pag->codportadorperiodo);
        DB::transaction(fn () => CaixaService::cancelarAvulso($pag));
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

    // ==== aba Itens do contas: o que cada item movimentou, para o acerto ====

    public function itemLancamentos(Request $request)
    {
        $filtros = $request->validate([
            'codcaixaitem' => 'nullable|integer',
            'codfilial' => 'nullable|integer',
            'transacao_de' => 'nullable|date',
            'transacao_ate' => 'nullable|date',
        ]);
        return ['data' => CaixaItemLancamentoService::listar($filtros)];
    }
}
