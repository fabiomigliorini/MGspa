<?php

namespace Mg\Stone;

use Illuminate\Support\Facades\Storage;

use Mg\Stone\Connect\ApiService;

use Carbon\Carbon;

class StoneTransacaoService
{

    public static function consultaPeloStoneId (StoneFilial $stoneFilial, $stone_transaction_id)
    {
        $token = StoneFilialService::verificaTokenValido($stoneFilial);

        // registra nova Pre Transacao na Stone
        $ret = ApiService::transactionSingleStone(
            $token,
            $stone_transaction_id
        );

        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }

        $stoneTransacao = static::processaTransacao($stoneFilial, $ret['transaction']);

        return $stoneTransacao;
    }

    public static function consultaPelaPreTransacao (StonePreTransacao $stonePreTransacao)
    {
        $token = StoneFilialService::verificaTokenValido($stonePreTransacao->StoneFilial);

        // registra nova Pre Transacao na Stone
        $ret = ApiService::transactionSinglePreTransacion(
            $token,
            $stonePreTransacao->pretransactionid
        );

        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }

        $stoneTransacao = static::processaTransacao($stonePreTransacao->StoneFilial, $ret['transaction']);

        return $stoneTransacao;
    }

    public static function processaTransacao (StoneFilial $stoneFilial, Array $transaction)
    {
        $stoneTransacao = StoneTransacao::firstOrNew([
            'stonetransactionid' => $transaction['stone_transaction_id']
        ]);
        $stoneTransacao->codstonefilial = $stoneFilial->codstonefilial;
        $stoneTransacao->siclostransactionid = $transaction['siclos_transaction_id'];
        if (!empty($transaction['pre_transaction_id'])) {
            if ($pre = StonePreTransacao::where('pretransactionid', $transaction['pre_transaction_id'])->first()) {
                $stoneTransacao->codstonepretransacao = $pre->codstonepretransacao;
            }
        }
        $stoneTransacao->criacao = Carbon::parse($transaction['created_at']);
        $stoneTransacao->status = $transaction['transaction_status'];
        if ($stoneTransacao->status = StoneTransacao::STATUS_CANCELADA && empyt($stoneTransacao->inativo)) {
            $stoneTransacao->inativo = Carbon::now();
        }
        $stoneTransacao->valor = $transaction['transaction_amount'];
        $stoneTransacao->valorliquido = $transaction['transaction_net_amount'];
        $stoneTransacao->parcelas = $transaction['installments_number'];
        $stoneTransacao->parcelas = $transaction['installments_number'];
        $stoneBandeira = StoneBandeira::firstOrCreate([
            'bandeira' => $transaction['card_brand']
        ]);
        $stoneTransacao->codstonebandeira = $stoneBandeira->codstonebandeira;
        $stoneTransacao->pagador = $transaction['card_holder_name'];
        $stoneTransacao->numerocartao = $transaction['card_number'];
        $stoneTransacao->autorizacao = $transaction['transaction_authorization_code'];
        $stoneTransacao->tipo = $transaction['payment_type'];
        $stoneTransacao->conciliada = $transaction['conciliation'];
        $stoneTransacao->save();
        return $stoneTransacao;
    }

}
