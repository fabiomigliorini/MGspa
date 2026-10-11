<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\PagamentoTituloAutorizador;
use Mg\Pagamento\PagamentoTituloService;

/**
 * Receber titulo e pagar vale/credito do cliente no PDV (M7 do plano doc-3,
 * absorvido pelo M6.1): baixa pelo wizard de cobranca (o mesmo servico do
 * contas, com o dinheiro na gaveta do PDV), estorno e recibo termico.
 */
class PdvPagamentoService
{
    // Cartao e PIX que a pessoa pagou (na venda ou em titulo), com o que
    // ainda da' para devolver: para registrar o cancelamento no cartao ou a
    // devolucao de PIX ao pagar o vale
    public static function originais(int $codpessoa): array
    {
        $pags = Pagamento::query()
            ->with(['Maquineta:codmaquineta,apelido', 'PagamentoContrarioS:codpagamento,codpagamentoorigem,estado,total'])
            ->whereIn('meio', [PagamentoService::MEIO_CREDITO, PagamentoService::MEIO_DEBITO, PagamentoService::MEIO_PIX])
            ->where('estado', PagamentoService::ESTADO_EFETIVADO)
            ->whereNull('codpagamentoorigem')
            ->where('transacao', '>=', now()->subYear())
            ->where(function ($w) use ($codpessoa) {
                $w->where('codpessoa', $codpessoa)
                    ->orWhereIn('codnegocio', fn($n) => $n->select('codnegocio')->from('tblnegocio')->where('codpessoa', $codpessoa));
            })
            ->orderBy('transacao', 'desc')
            ->limit(30)
            ->get();
        return $pags->map(function (Pagamento $p) {
            $devolvido = $p->PagamentoContrarioS->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)->sum('total');
            return [
                'codpagamento' => (int) $p->codpagamento,
                'meio' => $p->meio,
                'meiodescricao' => PagamentoService::descricao($p),
                'transacao' => $p->transacao,
                'codnegocio' => $p->codnegocio,
                'maquineta' => optional($p->Maquineta)->apelido,
                'bandeira' => $p->bandeira,
                'autorizacao' => $p->autorizacao,
                'parcelas' => $p->parcelas,
                'total' => (float) $p->total,
                'disponivel' => round($p->total - $devolvido, 2),
            ];
        })->filter(fn($p) => $p['disponivel'] > 0)->values()->all();
    }

    // Baixa pelo PDV: pelo papel do usuario no portador da forma (o
    // dinheiro entra = depositante; sai = operador); a gaveta do PDV so' vem
    // pre-selecionada
    public static function baixar(Pdv $pdv, array $dados): array
    {
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioBaixa(Auth::user()->codusuario, $dados, $pdv);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        return PagamentoTituloService::baixar($dados, $pdv);
    }

    // Pagamento visto no PDV: os dele, os da gaveta dele (transferencia,
    // M11) e os de portador em que o usuario tem papel (o PIX do banco usado
    // na baixa)
    public static function carregar(Pdv $pdv, int $codpagamento): Pagamento
    {
        $pag = PagamentoListaService::carregar($codpagamento);
        $daGaveta = !empty($pdv->codportador)
            && in_array($pdv->codportador, [$pag->codportadororigem, $pag->codportadordestino]);
        $portador = $pag->codportadorDoPagamento();
        $temPapel = !empty($portador)
            && \Mg\Portador\PortadorAutorizador::pode((int) $portador, \Mg\Portador\PortadorUsuario::PAPEL_DEPOSITANTE);
        if ($pag->codpdv != $pdv->codpdv && !$daGaveta && !$temPapel) {
            abort(403, 'Pagamento de outro PDV!');
        }
        return $pag;
    }

    // Desamarrar (estorna as baixas; o pagamento fica) e cancelar (so' o
    // manual ja' desamarrado). Caixa: os proprios, nas primeiras 2 horas;
    // Gerente: a filial
    public static function desamarrar(Pdv $pdv, int $codpagamento, string $justificativa, ?array $codmovimentos = null): Pagamento
    {
        $pag = static::carregar($pdv, $codpagamento);
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioEstorno($pag, Auth::user()->codusuario);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        return PagamentoTituloService::desamarrar($pag, $justificativa, $codmovimentos);
    }

    public static function cancelar(Pdv $pdv, int $codpagamento, string $justificativa): Pagamento
    {
        $pag = static::carregar($pdv, $codpagamento);
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioEstorno($pag, Auth::user()->codusuario);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        return PagamentoTituloService::cancelar($pag, $justificativa);
    }

    // Recibo termico (80mm) de um ou mais pagamentos do mesmo recebimento
    public static function imprimirRecibo(array $codpagamentos, string $impressora): void
    {
        $url = \URL::temporarySignedRoute('pdv.pagamento.recibo', now()->addMinutes(10), ['codpagamentos' => implode(',', $codpagamentos)]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "'
            . config('services.ably.key') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora
            . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);
    }
}
