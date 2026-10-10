<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Mg\Pagamento\Pagamento;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoPendenciaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\PagamentoTituloAutorizador;
use Mg\Pagamento\PagamentoTituloService;
use Mg\Usuario\Autorizador;

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

    // Baixa pelo PDV: receber e' de quem opera o caixa; pagar vale/credito
    // (sai dinheiro) so' Gerente da filial ou Administrador
    public static function baixar(Pdv $pdv, array $dados): array
    {
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioBaixa(Auth::user()->codusuario, $dados);
        if ($bloqueio !== null && str_starts_with($bloqueio, 'Encontro de contas')) {
            abort(403, $bloqueio);
        }
        if (PagamentoTituloService::liquido($dados['titulos'] ?? []) > 0) {
            if (!Autorizador::pode([]) && !Autorizador::pode(['Gerente'], $pdv->codfilial)) {
                abort(403, 'Pagar vale ou crédito do cliente só Gerente ou Administrador!');
            }
        }
        foreach ($dados['pagamentos'] ?? [] as $f) {
            if (empty($f['codpagamento'])) {
                continue;
            }
            $pag = Pagamento::findOrFail($f['codpagamento']);
            if (!empty($pag->codpdv) && $pag->codpdv != $pdv->codpdv) {
                abort(422, "O pagamento {$pag->codpagamento} é de outro PDV!");
            }
        }
        return PagamentoTituloService::baixar($dados, $pdv);
    }

    // Pagamento da listagem do PDV: so' os dele
    public static function carregar(Pdv $pdv, int $codpagamento): Pagamento
    {
        $pag = PagamentoListaService::carregar($codpagamento);
        // transferencia que chega na gaveta deste PDV (M11) tambem se ve
        $daGaveta = !empty($pdv->codportador)
            && in_array($pdv->codportador, [$pag->codportadororigem, $pag->codportadordestino]);
        if ($pag->codpdv != $pdv->codpdv && !$daGaveta) {
            abort(403, 'Pagamento de outro PDV!');
        }
        return $pag;
    }

    // "Ja' recebido" na venda: o pagamento sem amarracao (o PIX que confirmou
    // depois, o da venda cancelada) entra inteiro na venda aberta, se couber
    // no que falta. Um PIX maior: amarra o excedente como adiantamento e usa
    // o credito na venda.
    public static function amarrarVenda(Pdv $pdv, int $codnegocio, int $codpagamento): Pagamento
    {
        $negocio = Negocio::lockForUpdate()->findOrFail($codnegocio);
        if ($negocio->codnegociostatus != NegocioService::STATUS_ABERTO) {
            abort(422, 'Só se amarra pagamento em venda aberta!');
        }
        if (!empty($negocio->codpdv) && $negocio->codpdv != $pdv->codpdv) {
            abort(422, 'Venda de outro PDV!');
        }
        $pag = Pagamento::lockForUpdate()->findOrFail($codpagamento);
        if ($pag->estado != PagamentoService::ESTADO_EFETIVADO || !empty($pag->codnegocio)) {
            abort(422, "O pagamento {$pag->codpagamento} não está livre!");
        }
        if (empty($pag->codportadordestino) || !empty($pag->codportadororigem)) {
            abort(422, "O pagamento {$pag->codpagamento} não é uma entrada de dinheiro!");
        }
        if (PagamentoPendenciaService::movimentosAtivos($pag)->isNotEmpty()
            || abs(PagamentoPendenciaService::livre($pag) - (float) $pag->total) > 0.005) {
            abort(422, "O pagamento {$pag->codpagamento} já está amarrado em parte: na venda entra só o pagamento inteiro.");
        }
        $pagos = $negocio->PagamentoS()->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)->get()
            ->sum(fn ($p) => (float) $p->total + (float) $p->valortroco);
        $falta = round((float) $negocio->valortotal - $pagos - (float) $negocio->NegocioParcelaS()->sum('valor'), 2);
        if ((float) $pag->total > $falta + 0.005) {
            abort(422, 'O pagamento é de R$ ' . number_format($pag->total, 2, ',', '.')
                . ' e falta R$ ' . number_format($falta, 2, ',', '.')
                . ': amarre o excedente como adiantamento e use o crédito na venda.');
        }
        if (empty($pag->codpessoa)) {
            $pag->codpessoa = $negocio->codpessoa;
        }
        return PagamentoService::amarrarVenda($pag, $negocio->codnegocio, "Amarrado à venda #{$negocio->codnegocio} no PDV");
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
    public static function reciboPdf(array $codpagamentos): string
    {
        $pags = Pagamento::with([
            'Pessoa',
            'Filial.Pessoa',
            'PortadorDestino:codportador,portador',
            'PortadorOrigem:codportador,portador',
            'Maquineta:codmaquineta,apelido',
            'UsuarioCriacao:codusuario,usuario',
            'MovimentoTituloS.Titulo:codtitulo,numero,vencimento,codtipotitulo,observacao',
            'MovimentoTituloS.Titulo.TipoTitulo:codtipotitulo,tipotitulo',
        ])->whereIn('codpagamento', $codpagamentos)->orderBy('codpagamento')->get();
        if ($pags->isEmpty()) {
            abort(404, 'Pagamento não encontrado!');
        }
        $html = view('pagamento.recibo-termica', compact('pags'))->render();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        // bobina 80mm
        $dompdf->setPaper([0.0, 0.0, 226.77, 841.89], 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    public static function imprimirRecibo(array $codpagamentos, string $impressora): void
    {
        $url = \URL::temporarySignedRoute('pdv.pagamento.recibo', now()->addMinutes(10), ['codpagamentos' => implode(',', $codpagamentos)]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "'
            . config('services.ably.key') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora
            . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);
    }
}
