<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mg\Caixa\CaixaItem;
use Mg\Pdv\Pdv;
use Mg\Usuario\Autorizador;

/**
 * Ajuste, transferencia e item (doc-4, redefinicao do dinheiro): movimento do
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
            'codpdv' => 'nullable|integer',
        ]);
        $mov = DB::transaction(fn () => PortadorLancamentoService::lancarItem(
            PortadorPeriodo::with('Portador')->findOrFail($id),
            CaixaItem::findOrFail($dados['codcaixaitem']),
            (int) $dados['sinal'],
            $dados['linhas'],
            $dados['observacoes'] ?? null,
            !empty($dados['transacao']) ? Carbon::parse($dados['transacao']) : null,
            $this->livre($request)
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
            $this->livre($request)
        ));
        return $this->resposta($mov);
    }

    // a foto do bordero anexada depois
    public function foto(Request $request, int $id)
    {
        $request->validate(['anexoBase64' => 'required|string']);
        $mov = PortadorMovimento::with('Portador')->findOrFail($id);
        PortadorLancamentoService::anexarFoto($mov, $request->anexoBase64);
        return $this->resposta($mov);
    }

    // quem ve o portador ve a foto; o financeiro, pela conta corrente da
    // maquineta
    public function mostrarFoto(int $id, string $arquivo)
    {
        $mov = PortadorMovimento::with('Portador')->findOrFail($id);
        if (!PortadorAutorizador::pode($mov->codportador, PortadorUsuario::PAPEL_OPERADOR)) {
            Autorizador::autoriza(['Administrador', 'Financeiro']);
        }
        return Storage::disk(PortadorLancamentoService::DISCO)->response(
            PortadorLancamentoService::caminhoFoto($mov->codportadormovimento, $arquivo)
        );
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

    // ajuste, item, maquineta ou transferencia
    public function cancelar(Request $request, int $id)
    {
        $request->validate([
            'justificativa' => 'required|string|min:5|max:300',
            'codpdv' => 'nullable|integer',
        ]);
        $mov = PortadorMovimento::findOrFail($id);
        $livre = $this->livre($request);
        $mov = DB::transaction(fn () => match ($mov->tipo) {
            PortadorMovimento::TIPO_AJUSTE => PortadorLancamentoService::cancelarAjuste($mov, $request->justificativa, $livre),
            PortadorMovimento::TIPO_ITEM,
            PortadorMovimento::TIPO_MAQUINETA => PortadorLancamentoService::cancelarItem($mov, $request->justificativa, $livre),
            default => PortadorLancamentoService::cancelarTransferencia($mov, $request->justificativa, $livre),
        });
        return $this->resposta($mov);
    }
}
