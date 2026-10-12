<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Caixa\CaixaItem;

/**
 * Ajuste, transferencia e item (doc-4, redefinicao do dinheiro): movimento do
 * portador, nao pagamento. Rotas v1/portador-movimento. Devolvem a linha e os
 * periodos afetados (R14). Com o codpdv da gaveta (PortadorAutorizador::livre),
 * o caixa do PDV transfere (sangria, reforco), lanca o bordero da maquineta e
 * cancela a transferencia a confirmar e o bordero sem validar o papel; ajuste
 * e item, so' quem opera o portador.
 */
class PortadorLancamentoController extends Controller
{
    private function resposta(PortadorMovimento $mov)
    {
        $linhas = collect([$mov->fresh()]);
        if ($mov->codportadormovimentopar) {
            $linhas->push(PortadorMovimento::find($mov->codportadormovimentopar));
        }
        return ['data' => [
            'codportadormovimento' => $mov->codportadormovimento,
            'tipo' => $mov->tipo,
            'estado' => $mov->fresh()->estado,
            'portadordestino' => optional(optional($mov->Par)->Portador)->portador,
        ], 'periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::afetadosPor($linhas))];
    }

    // valor com sinal (positivo entrou), no periodo da tela
    public function ajuste(Request $request, int $id)
    {
        $dados = $request->validate([
            'valor' => 'required|numeric|not_in:0',
            'observacoes' => 'required|string|min:3|max:300',
            'transacao' => 'nullable|date',
        ]);
        $mov = DB::transaction(fn () => PortadorLancamentoService::ajustar(
            PortadorPeriodo::with('Portador')->findOrFail($id),
            (float) $dados['valor'],
            $dados['observacoes'],
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null
        ));
        return $this->resposta($mov);
    }

    // entrada (sinal 1) ou saida (-1) do item na gaveta, no periodo da tela
    public function item(Request $request, int $id)
    {
        $dados = $request->validate([
            'codcaixaitem' => 'required|integer|exists:tblcaixaitem,codcaixaitem',
            'sinal' => 'required|in:1,-1',
            'linhas' => 'required|array|min:1',
            'linhas.*.preco' => 'required|numeric|min:0.01',
            'linhas.*.quantidade' => 'required|integer|min:0',
            'linhas.*.descricao' => 'nullable|string|max:50',
            'observacoes' => 'nullable|string|max:300',
            'transacao' => 'nullable|date',
        ]);
        $mov = DB::transaction(fn () => PortadorLancamentoService::lancarItem(
            PortadorPeriodo::with('Portador')->findOrFail($id),
            CaixaItem::findOrFail($dados['codcaixaitem']),
            (int) $dados['sinal'],
            $dados['linhas'],
            $dados['observacoes'] ?? null,
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null
        ));
        return $this->resposta($mov);
    }

    // o total em dinheiro do bordero da maquineta de parceiro (com sinal:
    // negativo devolveu dinheiro), com a foto opcional, no periodo da tela
    public function maquineta(Request $request, int $id)
    {
        $dados = $request->validate([
            'codcaixaitem' => 'required|integer|exists:tblcaixaitem,codcaixaitem',
            'valor' => 'required|numeric|not_in:0',
            'observacoes' => 'nullable|string|max:300',
            'transacao' => 'nullable|date',
            'anexoBase64' => 'nullable|string',
            'codpdv' => 'nullable|integer',
        ]);
        $mov = DB::transaction(fn () => PortadorLancamentoService::lancarMaquineta(
            PortadorPeriodo::with('Portador')->findOrFail($id),
            CaixaItem::findOrFail($dados['codcaixaitem']),
            (float) $dados['valor'],
            $dados['observacoes'] ?? null,
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null,
            $dados['anexoBase64'] ?? null,
            PortadorAutorizador::livre()
        ));
        return $this->resposta($mov);
    }

    // a foto do bordero anexada depois
    public function foto(Request $request, int $id)
    {
        $request->validate(['anexoBase64' => 'required|string']);
        $mov = PortadorMovimento::with('Portador')->findOrFail($id);
        PortadorLancamentoService::anexarFoto($mov, $request->anexoBase64, PortadorAutorizador::livre());
        return $this->resposta($mov);
    }

    public function excluirFoto(int $id, string $arquivo)
    {
        $mov = PortadorMovimento::with('Portador')->findOrFail($id);
        PortadorLancamentoService::excluirFoto($mov, $arquivo, PortadorAutorizador::livre());
        return $this->resposta($mov);
    }

    public function mostrarFoto(int $id, string $arquivo)
    {
        return PortadorLancamentoService::mostrarFoto(PortadorMovimento::findOrFail($id), $arquivo, PortadorAutorizador::livre());
    }

    public function transferir(Request $request)
    {
        $dados = $request->validate([
            'codportadororigem' => 'required|integer|exists:tblportador,codportador',
            'codportadordestino' => 'required|integer|exists:tblportador,codportador|different:codportadororigem',
            'valor' => 'required|numeric|min:0.01',
            'observacoes' => 'nullable|string|max:300',
            'transacao' => 'nullable|date',
            'codportadorperiodo' => 'nullable|integer',
            'codpdv' => 'nullable|integer',
        ]);
        $mov = DB::transaction(fn () => PortadorLancamentoService::transferir(
            Portador::findOrFail($dados['codportadororigem']),
            Portador::findOrFail($dados['codportadordestino']),
            (float) $dados['valor'],
            $dados['observacoes'] ?? null,
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null,
            !empty($dados['codportadorperiodo']) ? PortadorPeriodo::with('Portador')->findOrFail($dados['codportadorperiodo']) : null,
            PortadorAutorizador::livre()
        ));
        return $this->resposta($mov);
    }

    public function confirmar(int $id)
    {
        $mov = DB::transaction(fn () => PortadorLancamentoService::confirmar(PortadorMovimento::findOrFail($id)));
        return $this->resposta($mov);
    }

    // alterar a data da linha (TASK-204): a linha vai para o periodo da data;
    // pagamento muda inteiro (razao, titulos), a transferencia nas duas pontas
    public function data(Request $request, int $id)
    {
        $dados = $request->validate([
            'transacao' => 'required|date',
            'justificativa' => 'required|string|min:5|max:300',
            'codpdv' => 'nullable|integer',
        ]);
        $mov = PortadorMovimento::with('Pagamento')->findOrFail($id);
        $mexidos = DB::transaction(fn () => LancamentoDataService::alterarMovimento(
            $mov,
            Carbon::parse($dados['transacao']),
            $dados['justificativa']
        ));
        $periodos = PortadorPeriodo::whereIn('codportadorperiodo', $mexidos->filter()->unique()->values())->get();
        return ['data' => ['codportadormovimento' => $mov->codportadormovimento],
            'periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::comSeguintes($periodos))];
    }

    // ajuste, item, maquineta ou transferencia
    public function cancelar(Request $request, int $id)
    {
        $request->validate([
            'justificativa' => 'required|string|min:5|max:300',
            'codpdv' => 'nullable|integer',
        ]);
        $mov = PortadorMovimento::findOrFail($id);
        $livre = PortadorAutorizador::livre();
        // no PDV: so' a transferencia a confirmar e o bordero da maquineta
        if ($livre && !PortadorLancamentoService::cancelaNoPdv($mov)) {
            abort(403, 'No caixa do PDV só se cancela a sangria ou o reforço a confirmar e o borderô de maquineta.');
        }
        $mov = DB::transaction(fn () => match ($mov->tipo) {
            PortadorMovimento::TIPO_AJUSTE => PortadorLancamentoService::cancelarAjuste($mov, $request->justificativa, $livre),
            PortadorMovimento::TIPO_ITEM,
            PortadorMovimento::TIPO_MAQUINETA => PortadorLancamentoService::cancelarItem($mov, $request->justificativa, $livre),
            default => PortadorLancamentoService::cancelarTransferencia($mov, $request->justificativa, $livre),
        });
        return $this->resposta($mov);
    }
}
