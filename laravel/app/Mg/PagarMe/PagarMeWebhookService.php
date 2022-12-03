<?php

namespace Mg\PagarMe;

use Illuminate\Support\Facades\Storage;

use Mg\Filial\Filial;

use Carbon\Carbon;

class PagarMeWebhookService
{

    // Cria ou Atualiza Filial na Stone
    public static function processar (string $arquivo)
    {
        $obj = PagarMeJsonService::carregar($arquivo);
        switch (strtolower($obj->type)) {
            case 'charge.paid':
            case 'charge.partial_canceled':
            case 'charge.refunded':
                static::parseChargePaid($obj);
                break;

            default:
                break;
        }
    }

    public static function parseChargePaid(object $obj)
    {
        // Filial
        $filial = PagarMeService::buscaFilial($obj->account->id);
        if (!$filial) {
            throw new \Exception("Account Id '{$obj->account->id}' da PagarMe não está vinculado à nenhuma filial!", 1);
        }

        // POS
        $pos = PagarMeService::buscaOuCriaPos($filial->codfilial, $obj->data->metadata->terminal_serial_number);

        // valor total do pedido
        $valor = $obj->data->order->amount??null;
        if ($valor) {
            $valor /=  100;
        }

        // valor total do pedido
        $valor = $obj->data->order->amount??null;
        if ($valor) {
            $valor /= 100;
        }

        // valor total pago do pedido
        $valorpago = $obj->data->paid_amount??null;
        if ($valorpago) {
            $valorpago /= 100;
        }

        // valor total cancelado do pedido
        $valorcancelado = $obj->data->canceled_amount??null;
        if ($valorcancelado) {
            $valorcancelado /= 100;
        }

        // Altera ou Cria o Pedido
        $ped = PagarMeService::alteraOuCriaPedido(
            $filial->codfilial,
            $obj->data->order->id,
            PagarMeService::STATUS_NUMBER[$obj->data->status],
            null,
            $pos->codpagarmepos,
            null,
            null,
            $obj->data->order->closed,
            ($obj->data->metadata->installment_type == 'MerchantFinanced'),
            $obj->data->metadata->installment_quantity??1,
            PagarMeService::TYPE_NUMBER[strtolower($obj->data->metadata->account_funding_source)]??1,
            $valor,
            $valorpago,
            $valorcancelado
        );

        $pp = PagarMeService::alteraOuCriaPagamento(
            $obj->data->last_transaction->id,
            $filial->codfilial,
            $pos->codpagarmepos,
            $obj->data->metadata->scheme_name,
            $ped->jurosloja,
            $ped->parcelas,
            $ped->tipo,
            $ped->codpagarmepedido,
            $obj->data->metadata->authorization_code,
            $obj->data->metadata->initiator_transaction_key,
            $obj->data->code,
            $obj->data->metadata->account_holder_name,
            $obj->data->metadata->transaction_timestamp,
            $obj->data->last_transaction->status,
            $obj->data->last_transaction->amount / 100
        );

        // reconfere totais do pedido pra quando o webhook chega fora de ordem
        // exemplo quando o json de cancelamento chega antes do json de pagamento
        PagarMeService::confereTotaisPedido($ped);

        // caso PagarMePedido totalmente pago, marca ele como "paid"
        PagarMeService::fecharPedidoSePago($ped);

        // cria forma de pagamento e atrela ao negocio
        PagarMeService::vincularNegocioFormaPagamento($ped);

        // retorna o pagamento
        return $pp;
    }
}
