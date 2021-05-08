<?php

namespace Mg\Stone;

use Illuminate\Support\Facades\Storage;

use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Stone\Connect\ApiService;

use Carbon\Carbon;

class StonePreTransacaoService
{

    // Cria ou Atualiza Filial na Stone
    public static function create (
        StoneFilial $stoneFilial,
        $valor,
        $titulo = null,
        $codnegocio = null,
        $tipo = null,
        $parcelas = null,
        $tipoparcelamento = null
    )
    {
        $token = StoneFilialService::verificaTokenValido($stoneFilial);

        // registra nova Pre Transacao na Stone
        $ret = ApiService::preTransactionCreate(
            $token,
            $stoneFilial->establishmentid,
            $valor,
            $titulo,
            $tipo,
            $parcelas,
            $tipoparcelamento
        );

        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }

        $stonePreTransacao = StonePreTransacao::create([
            'codstonefilial' => $stoneFilial->codstonefilial,
            'pretransactionid' => $ret['pre_transaction']['pre_transaction_id'],
            'valor' => $valor,
            'titulo' => $titulo,
            'codnegocio' => $codnegocio,
            'tipo' => $tipo,
            'parcelas' => $parcelas,
            'tipoparcelamento' => $tipoparcelamento,
            'processada' => false,
            'ativa' => true,
            'token' => $ret['pre_transaction']['pre_transaction_token'],
            'status' => $ret['pre_transaction']['status'],
        ]);

        return $stonePreTransacao;
    }

    public static function consulta (StonePreTransacao $stonePreTransacao)
    {
        $token = StoneFilialService::verificaTokenValido($stonePreTransacao->StoneFilial);

        // registra nova Pre Transacao na Stone
        $ret = ApiService::preTransactionSingle(
            $token,
            $stonePreTransacao->pretransactionid
        );

        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }

        $stonePreTransacao->update([
            'valor' => $ret['pre_transaction']['amount'],
            'processada' => $ret['pre_transaction']['processed'],
            'ativa' => $ret['pre_transaction']['is_active'],
            'token' => $ret['pre_transaction']['pre_transaction_token'],
            'tipo' => $ret['pre_transaction']['payment']['type'],
            'parcelas' => $ret['pre_transaction']['payment']['installment'],
            'tipoparcelamento' => $ret['pre_transaction']['payment']['installment_type'],
        ]);

        if ($ret['pre_transaction']['processed']) {
            $stoneTransacao = StoneTransacaoService::consultaPelaPreTransacao($stonePreTransacao);
        }

        return $stonePreTransacao;
    }

}
