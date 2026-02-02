<?php

namespace Mg\Lio;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
// use Illuminate\Support\Facades\DB;

use Mg\Pessoa\Pessoa;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\FormaPagamento\FormaPagamento;

class LioService
{

    public static function parse($id)
    {
        // Carrega os arquivos JSON
        $order = LioJsonService::carregarOrder($id);
        // $pagamento = LioJsonService::carregarPagamento($id);

        // Cria o Status do Pedido
        $status = LioPedidoStatus::firstOrNew([
            'sigla' => $order->status
        ]);
        if (!$status->exists) {
            $status->liopedidostatus = $status->sigla;
            $status->save();
        }

        // Cria o Pedido
        $pedido = LioPedido::firstOrNew([
            'uuid' => $order->id
        ]);
        $pedido->codliopedidostatus = $status->codliopedidostatus;
        $pedido->valortotal = $order->price / 100;
        $pedido->valorpago = $order->paidAmount / 100;
        $pedido->valorsaldo = $order->pendingAmount / 100;
        $pedido->referencia = $order->reference;
        $pedido->alteracao = Carbon::parse($order->updatedAt);
        if (empty($pedido->criacao)) {
            $pedido->criacao = Carbon::parse($order->createdAt);
        }
        $pedido->save();

        // Percorre os Pagamentos
        foreach ($order->payments as $payment) {

            // Cria Bandeira
            $bandeira = LioBandeiraCartao::firstOrNew([
                'siglalio' => $payment->brand
            ]);
            if (!$bandeira->exists) {
                $bandeira->bandeiracartao = $bandeira->siglalio;
                $bandeira->save();
            }

            // Cria Produto
            $produto = LioProduto::firstOrNew([
                'codigoprimario' => $payment->paymentFields->primaryProductCode,
                'codigosecundario' => $payment->paymentFields->secondaryProductCode
            ]);
            if (!$produto->exists) {
                $produto->nomeprimario = $payment->paymentFields->primaryProductName ?? null;
                $produto->nomesecundario = $payment->paymentFields->secondaryProductName ?? null;
                $produto->lioproduto = $payment->paymentFields->productName ?? "{$produto->nomeprimario} /{$produto->nomesecundario}";
                $produto->save();
            }

            // Cria Terminal
            $terminal = LioTerminal::firstOrNew([
                'terminal' => $payment->terminal
            ]);
            if (!$terminal->exists) {
                $terminal->lioterminal = $terminal->terminal;
                if (isset($payment->paymentFields->document)) {
                    $pessoas = Pessoa::where('cnpj', $payment->paymentFields->document)->get();
                    foreach ($pessoas as $pessoa) {
                        foreach ($pessoa->FilialS as $filial) {
                            $terminal->codfilial = $filial->codfilial;
                        }
                    }
                }
                $terminal->save();
            }

            // cria pagamentos
            $pagamento = LioPedidoPagamento::firstOrNew([
                'codliopedido' => $pedido->codliopedido,
                'uuid' => $payment->id
            ]);
            $pagamento->valor = $payment->amount / 100;
            $pagamento->parcelas = $payment->paymentFields->numberOfQuotas ?? null;
            $pagamento->cartao = $payment->paymentFields->pan ?? $payment->mask ?? null;
            $pagamento->autorizada = ($payment->paymentFields->statusCode == 1);
            $pagamento->autorizacao = $payment->authCode ?? null;
            $pagamento->nsu = $payment->cieloCode ?? null;
            $pagamento->codliobandeiracartao = $bandeira->codliobandeiracartao;
            $pagamento->nome = $payment->paymentFields->clientName ?? null;
            if (isset($payment->paymentFields->originalTransactionDate)) {
                try {
                    $pagamento->transacao = carbon::parse($payment->paymentFields->originalTransactionDate);
                } catch (\Exception $e) {
                    $pagamento->transacao = carbon::createFromFormat('d/m/y', $payment->paymentFields->originalTransactionDate);
                }
            }
            $pagamento->codigov40 = $payment->paymentFields->v40Code ?? null;
            $pagamento->codlioproduto = $produto->codlioproduto;
            $pagamento->codlioterminal = $terminal->codlioterminal;
            if (empty($pagamento->criacao)) {
                $pagamento->criacao = carbon::createFromTimestampMs($payment->requestDate);
            }
            $pagamento->alteracao = carbon::createFromTimestampMs($payment->requestDate);
            $pagamento->save();
        }

        if (!empty($order->number)) {
            $n = Negocio::where(['codnegocio' => $order->number])->first();
            if ($n) {
                $nfp = NegocioFormaPagamento::firstOrNew([
                    'codliopedido' => $pedido->codliopedido
                ]);
                $nfp->codnegocio = $order->number;
                $nfp->valorpagamento = $pedido->valorpago;
                $fp = FormaPagamento::firstOrNew(['lio' => true, 'integracao' => true]);
                if (!$fp->exists) {
                    $fp->formapagamento = 'Cielo Lio';
                    $fp->avista = true;
                    $fp->integracao = true;
                    $fp->save();
                }
                $nfp->codformapagamento = $fp->codformapagamento;
                $nfp->save();
                $fechado = \Mg\Negocio\NegocioService::fecharSePago($n);
            }
        }


        return $pedido;
    }
}
