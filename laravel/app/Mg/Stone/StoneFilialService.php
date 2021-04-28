<?php

namespace Mg\Stone;

use Illuminate\Support\Facades\Storage;

use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Stone\Connect\ApiService;

use Carbon\Carbon;

class StoneFilialService
{

    // Cria ou Atualiza Filial na Stone
    public static function create ($codfilial, $stonecode, $chaveprivada)
    {
        // Busca Filial se ja estiver cadastrada
        $filial = Filial::findOrFail($codfilial);
        $stoneFilial = StoneFilial::firstOrNew([
            'codfilial' => $filial->codfilial
        ]);

        // associa variaveis recebidas por parametro
        $stoneFilial->chaveprivada = $chaveprivada;
        $stoneFilial->stonecode = $stonecode;
        $stoneFilial->save();

        // busca cadastro da filial na stone
        static::sincroniza($stoneFilial);
        $stoneFilial->refresh();

        // Atualiza Token
        $token = static::verificaTokenValido($stoneFilial);

        // se nao esta registrado na stone
        if (empty($stoneFilial->establishmentid)) {

            // registra novo Establishment na Stone
            $ret = ApiService::establishmentCreateExistingStone(
                $token,
                $stoneFilial->Filial->Pessoa->pessoa,
                $stoneFilial->Filial->Pessoa->fantasia,
                str_pad($stoneFilial->Filial->Pessoa->cnpj, 14, "0", STR_PAD_LEFT),
                number_format($stoneFilial->stonecode, 0, '', '')
            );
            if (!$ret['success']) {
                throw new \Exception($ret['msg'], 1);
            }

            // Salva o ID gerado pela Stone
            $stoneFilial->update([
                'establishmentid' => $ret['establishment']['id'],
            ]);
        }

        static::registraWebhook ($stoneFilial);

        // retorna filial criada
        return $stoneFilial;
    }

    // Verifica se o Token salvo na Tabela ainda é valido
    public static function verificaTokenValido (StoneFilial $stoneFilial)
    {
        if (!empty($stoneFilial->datatoken)) {
            if ($stoneFilial->datatoken->isToday()) {
                return $stoneFilial->token;
            }
        }
        $ret = ApiService::token($stoneFilial->chaveprivada);
        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }
        $stoneFilial->update([
            'token' => $ret['token'],
            'datatoken' => Carbon::now(),
        ]);
        return $ret['token'];
    }

    // Sincroniza os cadastros das filiais com a Stone
    public static function sincroniza (StoneFilial $stoneFilial)
    {
        $token = static::verificaTokenValido($stoneFilial);
        $ret = ApiService::EstablishmentGetAll($token);
        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }
        foreach ($ret['establishments'] as $est) {
            foreach (Pessoa::where(['cnpj' => $est['document']])->get() as $pessoa){
                foreach ($pessoa->FilialS as $filial) {
                    foreach ($filial->StoneFilialS as $sf) {
                        $dados = [
                            'stonecode' => $est['stone_code'],
                            'establishmentid' => $est['id'],
                            'disponivelloja' => $est['mamba_released'],
                        ];
                        if (!$est['establishment_is_active']) {
                            $dados['inativo'] = Carbon::now();
                        }
                        $sf->update($dados);
                    }
                }
            }
        }
    }

    // Consulta Webhooks Cadastrados
    public static function consultaWebhook (StoneFilial $stoneFilial)
    {
        $token = static::verificaTokenValido($stoneFilial);
        $ret = ApiService::webhook($token);
        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }
        return $ret['webhookList'];
    }

    // Registra Webhooks para Filial
    public static function registraWebhook (StoneFilial $stoneFilial)
    {
        // Verifica Token
        $token = static::verificaTokenValido($stoneFilial);

        // Busca listagem dos Webooks cadastrados
        $webhooks = collect(static::consultaWebhook ($stoneFilial));

        // Procura e registra Webook "pos-application"
        $webhook = $webhooks->first(function ($item, $key) use ($stoneFilial) {
            return
                $item['is_active'] == 1
                && $item['webhook'] == "Aplicação liberada no POS"
                && $item['establishment_id'] == $stoneFilial->establishmentid
                ;
        });
        if (!$webhook) {
            $ret = ApiService::webhookPosApplication($token, $stoneFilial->establishmentid);
            if (!$ret['success']) {
                throw new \Exception($ret['msg'], 1);
            }
        }

        // Procura e registra Webook "pre-transaction-status"
        $webhook = $webhooks->first(function ($item, $key) use ($stoneFilial) {
            return
                $item['is_active'] == 1
                && $item['webhook'] == "Status de pré-transação"
                && $item['establishment_id'] == $stoneFilial->establishmentid
                ;
        });
        if (!$webhook) {
            $ret = ApiService::webhookPreTransactionStatus($token, $stoneFilial->establishmentid);
            if (!$ret['success']) {
                throw new \Exception($ret['msg'], 1);
            }
        }

        // Procura e registra Webook "processed-transaction"
        $webhook = $webhooks->first(function ($item, $key) use ($stoneFilial) {
            return
                $item['is_active'] == 1
                && $item['webhook'] == "Status de transação"
                && $item['establishment_id'] == $stoneFilial->establishmentid
                ;
        });
        if (!$webhook) {
            $ret = ApiService::webhookProcessedTransaction($token, $stoneFilial->establishmentid);
            if (!$ret['success']) {
                throw new \Exception($ret['msg'], 1);
            }
        }

        // Procura e registra Webook "print-note-status"
        $webhook = $webhooks->first(function ($item, $key) use ($stoneFilial) {
            return
                $item['is_active'] == 1
                && $item['webhook'] == "Status de impressão"
                && $item['establishment_id'] == $stoneFilial->establishmentid
                ;
        });
        if (!$webhook) {
            $ret = ApiService::webhookPrintNoteStatus($token, $stoneFilial->establishmentid);
            if (!$ret['success']) {
                throw new \Exception($ret['msg'], 1);
            }
        }
    }

}
