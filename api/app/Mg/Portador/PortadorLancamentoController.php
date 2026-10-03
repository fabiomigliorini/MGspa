<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Pdv\Pdv;

/**
 * Ajuste e transferencia (doc-4, redefinicao do dinheiro): movimento do
 * portador, nao pagamento. Rotas v1/portador-movimento. Devolvem a linha e os
 * periodos afetados (R14). Com o codpdv da gaveta, o PDV nao valida o papel
 * nela.
 */
class PortadorLancamentoController extends Controller
{
    // a gaveta do PDV que pede (livre de papel)
    private function livre(Request $request): ?int
    {
        $codpdv = $request->input('codpdv');
        return $codpdv ? optional(Pdv::find($codpdv))->codportador : null;
    }

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
            'codpdv' => 'nullable|integer',
        ]);
        $mov = DB::transaction(fn () => PortadorLancamentoService::ajustar(
            PortadorPeriodo::with('Portador')->findOrFail($id),
            (float) $dados['valor'],
            $dados['observacoes'],
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null,
            $this->livre($request)
        ));
        return $this->resposta($mov);
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
            $this->livre($request)
        ));
        return $this->resposta($mov);
    }

    public function confirmar(int $id)
    {
        $mov = DB::transaction(fn () => PortadorLancamentoService::confirmar(PortadorMovimento::findOrFail($id)));
        return $this->resposta($mov);
    }

    // ajuste ou transferencia
    public function cancelar(Request $request, int $id)
    {
        $request->validate([
            'justificativa' => 'required|string|min:5|max:300',
            'codpdv' => 'nullable|integer',
        ]);
        $mov = PortadorMovimento::findOrFail($id);
        $mov = DB::transaction(fn () => $mov->tipo == PortadorMovimento::TIPO_AJUSTE
            ? PortadorLancamentoService::cancelarAjuste($mov, $request->justificativa, $this->livre($request))
            : PortadorLancamentoService::cancelarTransferencia($mov, $request->justificativa, $this->livre($request)));
        return $this->resposta($mov);
    }
}
