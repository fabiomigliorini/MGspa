<?php

namespace Mg\Portador;

use Mg\Banco\Banco;

class PortadorService
{
    public static function buscarPortadorOfx($routingNumber, $accountNumber)
    {

        // Localiza o Banco
        $banco = Banco::where([
            'numerobanco' => $routingNumber
        ])->firstOrFail();

        // Banco do Brasil - PJ
        // Ex: 13387-6
        if ($pos = strpos($accountNumber, '-')) {
            $conta = substr($accountNumber, 0, $pos);
            $contadigito = substr($accountNumber, $pos + 1);
            if ($portador = Portador::where([
                'codbanco' => $banco->codbanco,
                'conta' => $conta,
                'contadigito' => $contadigito,
            ])->first()) {
                return $portador;
            };
        }

        // Bradesco - PF
        // Ex: 953/195
        if ($pos = strpos($accountNumber, '/')) {
            $agencia = substr($accountNumber, 0, $pos);
            $conta = substr($accountNumber, $pos + 1);
            if ($portador = Portador::where([
                'codbanco' => $banco->codbanco,
                'agencia' => $agencia,
                'conta' => $conta,
            ])->first()) {
                return $portador;
            };
        }

        // considera quem o $accountNumber é somente a conta
        $conta = preg_replace("/[^0-9]/", "", $accountNumber);
        if ($portador = Portador::where([
            'codbanco' => $banco->codbanco,
            'conta' => $conta,
        ])->first()) {
            return $portador;
        };

        throw new \Exception("Não localizado nenhum portador para a conta '{$accountNumber}'!", 1);
    }

    public static function importarOfx ($ofxString)
    {

        // Carrega o arquivo
        $ofxParser = new \OfxParser\Parser();
        $ofx = $ofxParser->loadFromString($ofxString);

        // Localiza o Portador
        $bankAccount = reset($ofx->bankAccounts);
        $portador = static::buscarPortadorOfx($bankAccount->routingNumber, $bankAccount->accountNumber);

        // Ignorados por enquanto
        // $startDate = $bankAccount->statement->startDate;
        // $endDate = $bankAccount->statement->endDate;

        // Get the statement transactions for the account
        $transactions = $bankAccount->statement->transactions;
        $registros = 0;
        $falhas = 0;
        foreach ($transactions as $transaction) {

            // determina tipo do movimento
            $tipo = ExtratoBancarioTipoMovimento::firstOrNew([
                'trntype' => $transaction->type,
            ]);
            if (empty($tipo->codextratobancariotipomovimento)) {
                $tipo->tipo = $transaction->type;
                $tipo->sigla = substr($transaction->type, 0, 3);
                $tipo->save();
            }

            // verifica se o registro já existe
            $mov = ExtratoBancario::firstOrNew([
                'codportador' => $portador->codportador,
                'fitid' => $transaction->uniqueId,
            ]);
            $mov->codextratobancariotipomovimento = $tipo->codextratobancariotipomovimento;
            $mov->lancamento = $transaction->date;
            $mov->valor =  $transaction->amount;
            $mov->numero =  $transaction->checkNumber;
            $mov->observacoes =  $transaction->memo;
            if (!$mov->save()) {
                $falhas++;
            };

            $registros++;
        }

        return [
            'codportador' => $portador->codportador,
            'portador' => $portador->portador,
            'registros' => $registros,
            'falhas' => $falhas,
        ];
    }

}
