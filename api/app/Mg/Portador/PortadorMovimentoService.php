<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Mg\Caixa\CaixaService;
use Mg\Conferencia\ConferenciaService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;

/**
 * Razao do dinheiro (M10 doc-3): as linhas de tblportadormovimento de cada
 * pagamento. Chamado explicitamente por quem grava pagamento efetivado ou
 * muda um (PagamentoService, PagamentoTituloService, correcao da
 * conferencia): caminho novo que grave pagamento precisa chamar o
 * sincronizar.
 */
class PortadorMovimentoService
{
    // meios em que o dinheiro cai na hora; cartao (com prazo por parcela)
    // e Mercos Pay entram no M14; cheque, vale e os internos nao lancam
    const MEIOS = [
        PagamentoService::MEIO_DINHEIRO,
        PagamentoService::MEIO_BOLETO,
        PagamentoService::MEIO_DEPOSITO,
        PagamentoService::MEIO_PIX,
        PagamentoService::MEIO_TRANSFERENCIA,
    ];

    // as linhas que o pagamento deveria ter agora: uma por lado com
    // portador (destino +total, origem -total). A transferencia (M11) lanca
    // ja' no registro, ainda pendente "a confirmar" (decisoes 13 e 22)
    public static function desejadas(Pagamento $pag): array
    {
        $aConfirmar = $pag->estado == PagamentoService::ESTADO_PENDENTE
            && PagamentoService::ehTransferencia($pag);
        if ($pag->estado != PagamentoService::ESTADO_EFETIVADO && !$aConfirmar) {
            return [];
        }
        if (!in_array((int) $pag->meio, static::MEIOS)) {
            return [];
        }
        if (round((float) $pag->total, 2) == 0) {
            return [];
        }
        $transacao = Carbon::parse($pag->transacao);
        // o razao comeca no go-live, como as conferencias
        if ($transacao->lt(ConferenciaService::inicio())) {
            return [];
        }
        if (!empty($pag->codportadororigem) && $pag->codportadororigem == $pag->codportadordestino) {
            return [];
        }
        $ret = [];
        foreach ([[$pag->codportadordestino, 1], [$pag->codportadororigem, -1]] as [$codportador, $sinal]) {
            if (empty($codportador)) {
                continue;
            }
            $portador = Portador::findOrFail($codportador);
            $ret[] = [
                'codportador' => (int) $portador->codportador,
                'codportadorperiodo' => static::periodo($pag, $portador, $transacao)->codportadorperiodo,
                'valor' => round($sinal * (float) $pag->total, 2),
                'transacao' => $transacao->format('Y-m-d H:i:s'),
                'parcela' => null,
            ];
        }
        return $ret;
    }

    // gaveta: a sessao que o M9 gravou no pagamento (ou a do momento);
    // demais: o periodo corrente, que nasce sozinho
    private static function periodo(Pagamento $pag, Portador $portador, Carbon $transacao): PortadorPeriodo
    {
        if (!$portador->ehGaveta()) {
            return PortadorPeriodoService::corrente($portador);
        }
        $sessao = null;
        if (!empty($pag->codportadorperiodo)) {
            $sessao = PortadorPeriodo::find($pag->codportadorperiodo);
            if ($sessao && $sessao->codportador != $portador->codportador) {
                $sessao = null;
            }
        }
        $sessao = $sessao ?? CaixaService::sessaoDe($portador->codportador, $transacao);
        if (!$sessao) {
            abort(422, "Não há sessão do caixa {$portador->portador} em {$transacao->format('d/m/Y H:i')} para o pagamento {$pag->codpagamento}.");
        }
        return $sessao;
    }

    private static function chave(array $l): string
    {
        return implode('|', [
            $l['codportador'],
            $l['codportadorperiodo'],
            number_format($l['valor'], 2, '.', ''),
            $l['transacao'],
            $l['parcela'] ?? 0,
        ]);
    }

    private static function exigirMutavel(int $codportadorperiodo): void
    {
        $periodo = PortadorPeriodo::findOrFail($codportadorperiodo);
        if (PortadorPeriodoService::imutavel($periodo)) {
            $o = $periodo->Portador->ehGaveta() ? 'o caixa' : 'o período';
            $estado = empty($periodo->conferencia) ? 'fechado' : 'conferido';
            abort(422, "O razão de {$periodo->Portador->portador} ("
                . PortadorPeriodoService::descricao($periodo)
                . ") já foi {$estado}: reabra {$o} antes de mudar este pagamento.");
        }
    }

    // deixa o razao igual ao pagamento: inativa as linhas que sobram e cria
    // as que faltam (idempotente)
    public static function sincronizar(Pagamento $pag): void
    {
        $desejadas = [];
        foreach (static::desejadas($pag) as $l) {
            $desejadas[static::chave($l)] = $l;
        }
        $ativas = PortadorMovimento::where('codpagamento', $pag->codpagamento)
            ->whereNull('inativo')
            ->get();
        $sobram = [];
        foreach ($ativas as $mov) {
            $chave = static::chave([
                'codportador' => $mov->codportador,
                'codportadorperiodo' => $mov->codportadorperiodo,
                'valor' => (float) $mov->valor,
                'transacao' => $mov->transacao->format('Y-m-d H:i:s'),
                'parcela' => $mov->parcela,
            ]);
            if (isset($desejadas[$chave])) {
                unset($desejadas[$chave]);
                continue;
            }
            $sobram[] = $mov;
        }
        foreach ($sobram as $mov) {
            static::exigirMutavel($mov->codportadorperiodo);
            $mov->inativo = Carbon::now();
            $mov->save();
        }
        foreach ($desejadas as $l) {
            static::exigirMutavel($l['codportadorperiodo']);
            PortadorMovimento::create($l + ['codpagamento' => $pag->codpagamento]);
        }
    }
}
