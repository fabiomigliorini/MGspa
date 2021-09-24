<?php

namespace Mg\Portador;

class PortadorService
{
    public static function importarOfx($ofxString)
    {
        $ofxParser = new \OfxParser\Parser();
        $ofx = $ofxParser->loadFromString($ofxString);
        $bankAccount = reset($ofx->bankAccounts);
        // Get the statement start and end dates
        $startDate = $bankAccount->statement->startDate;
        $endDate = $bankAccount->statement->endDate;

        // dd($startDate);

        // Get the statement transactions for the account
        $transactions = $bankAccount->statement->transactions;
        foreach ($transactions as $transaction) {
            dd($transaction->amount);
            dd($transaction);
        }
        dd($ofx);

        dd($ofxString);
    }

}
