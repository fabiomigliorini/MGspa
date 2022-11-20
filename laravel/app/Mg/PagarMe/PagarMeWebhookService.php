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

        // Busca na filial transacao com aquele id
        $pp = PagarMePagamento::firstOrNew([
            'idtransacao' => $obj->data->last_transaction->id,
            'codfilial' => $filial->codfilial
        ]);

        // POS
        $pos = PagarMeService::buscaOuCriaPos($filial->codfilial, $obj->data->metadata->terminal_serial_number);
        $pp->codpagarmepos = $pos->codpagarmepos;

        // Bandeira
        $band = PagarMeService::buscaOuCriaBandeira($obj->data->metadata->scheme_name);
        $pp->codpagarmebandeira = $band->codpagarmebandeira;

        // tipo, parcelas e juros por conta de quem
        $pp->jurosloja = ($obj->data->metadata->installment_type == 'MerchantFinanced')?true:false;
        $pp->parcelas = $obj->data->metadata->installment_quantity;
        switch (strtolower($obj->data->metadata->account_funding_source)) {
            case 'debit':
                $pp->tipo = 1;
                break;
            case 'credit':
                $pp->tipo = 2;
                break;
            case 'voucher':
                $pp->tipo = 3;
                break;
            case 'prepaid':
                $pp->tipo = 4;
                break;
        }

        // valor total do pedido
        $valor = $obj->data->order->amount??null;
        if ($valor) {
            $valor = $valor / 100;
        }

        // valor total pago do pedido
        $valorpago = $obj->data->paid_amount??null;
        if ($valorpago) {
            $valorpago = $valorpago / 100;
        }

        // valor total cancelado do pedido
        $valorcancelado = $obj->data->canceled_amount??null;
        if ($valorcancelado) {
            $valorcancelado = $valorcancelado / 100;
        }

        // Altera ou Cria o Pedido
        $ped = PagarMeService::alteraOuCriaPedido(
            $filial->codfilial,
            $obj->data->order->id,
            null,
            $pos->codpagarmepos,
            null,
            null,
            $obj->data->order->closed,
            $pp->jurosloja,
            $pp->parcelas,
            $pp->tipo,
            $valor,
            $valorpago,
            $valorcancelado
        );
        $pp->codpagarmepedido = $ped->codpagarmepedido;

        // dados de autorizacao e nome do cliente
        $pp->autorizacao = $obj->data->metadata->authorization_code;
        $pp->identificador = $obj->data->metadata->initiator_transaction_key;
        $pp->nsu = $obj->data->code;
        $pp->nome = $obj->data->metadata->account_holder_name;

        // data da transacao - covnerte Timezone de UTC pra America/Cuiaba
        $transacao = Carbon::parse($obj->data->metadata->transaction_timestamp);
        $transacao->setTimezone(config('app.timezone'));
        $pp->transacao = $transacao;

        // decide se é pagamento ou cancelamento pelo status da transacao
        switch (strtolower($obj->data->last_transaction->status)) {
            case 'paid':
                $pp->valorpagamento = $obj->data->last_transaction->amount / 100;
                $pp->valorcancelamento = null;
                break;

            case 'canceled':
                $pp->valorpagamento = null;
                $pp->valorcancelamento = $obj->data->last_transaction->amount / 100;
                break;

        }

        // salva
        $pp->save();

        // reconfere totais do pedido pra quando o webhook chega fora de ordem
        // exemplo quando o json de cancelamento chega antes do json de pagamento
        PagarMeService::confereTotaisPedido($ped);

        // retorna o pagamento
        return $pp;
    }
}
