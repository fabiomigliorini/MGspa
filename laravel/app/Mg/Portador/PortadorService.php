<?php

namespace Mg\Portador;

use Illuminate\Support\Facades\DB;
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

    public static function listaSaldos($dataInicial, $dataFinal)
    {
        $sql = '
            SELECT
            f.codfilial,
            f.filial,
            b.codbanco,
            b.banco,
            p.codportador,
            p.portador,
            COALESCE(SUM(e.valor), 0) AS saldo
        FROM tblportador AS p
        LEFT JOIN tblfilial AS f
          ON p.codfilial = f.codfilial
        LEFT JOIN tblbanco AS b
          ON p.codbanco = b.codbanco
        INNER JOIN tblextratobancario AS e
          ON e.codportador = p.codportador
         AND e.lancamento BETWEEN :data_inicial AND :data_final
        GROUP BY
            f.codfilial,
            f.filial,
            b.codbanco,
            b.banco,
            p.codportador,
            p.portador
        ORDER BY
            f.codfilial,
            b.codbanco,
            p.codportador
        ';

        $data = DB::select($sql, [
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal
        ]);

        return self::montaEstruturaSaldo($data);
    }

    private static function montaEstruturaSaldo(array $linhas): array
    {
        // monta os portadores de um banco
        $montarPortadores = function($itensBanco): array {
            return collect($itensBanco)
                ->map(function($registro) {
                    return [
                        'codportador' => (int)   $registro->codportador,
                        'portador'    =>         $registro->portador,
                        'saldo'       => (float) $registro->saldo,
                    ];
                })
                ->values()
                ->all();
        };

        // soma os saldos de um único banco
        $somarBanco = function($itensBanco) use ($montarPortadores): float {
            $portadores = $montarPortadores($itensBanco);
            return array_sum(array_column($portadores, 'saldo'));
        };

        // monta a lista de bancos de uma filial
        $montarBancos = function($itensFilial) use ($montarPortadores, $somarBanco): array {
            return collect($itensFilial)
                ->groupBy('codbanco')
                ->map(function($itensBanco) use ($montarPortadores, $somarBanco) {
                    return [
                        'codbanco'   => (int)   $itensBanco->first()->codbanco,
                        'nome'       =>         $itensBanco->first()->banco,
                        'portadores' =>         $montarPortadores($itensBanco),
                        'totalBanco' => (float) $somarBanco($itensBanco),
                    ];
                })
                ->values()
                ->all();
        };

        // soma todos os totais de banco para dar o total da filial
        $somarFilial = function($itensFilial) use ($montarBancos): float {
            $bancos = $montarBancos($itensFilial);
            return array_sum(array_column($bancos, 'totalBanco'));
        };

        // monta todas as filiais
        $filiais = collect($linhas)
            ->groupBy('codfilial')
            ->map(function($itensFilial) use ($montarBancos, $somarFilial) {
                return [
                    'codfilial'   => (int)   $itensFilial->first()->codfilial,
                    'nome'        =>         $itensFilial->first()->filial,
                    'totalFilial' => (float) $somarFilial($itensFilial),
                    'bancos'      =>         $montarBancos($itensFilial),
                ];
            })
            ->values()
            ->all();

        // total por banco em todo o conjunto
        $totalPorBanco = collect($linhas)
            ->groupBy('codbanco')
            ->map(function($itensBanco) use ($montarPortadores) {
                $saldo = array_sum(array_column(
                    $montarPortadores($itensBanco),
                    'saldo'
                ));
                return [
                    'codbanco' => (int)   $itensBanco->first()->codbanco,
                    'valor'    => (float) $saldo,
                ];
            })
            ->values()
            ->all();

        // total geral
        $totalGeral = array_sum(array_column($totalPorBanco, 'valor'));

        return [
            'filiais'       => $filiais,
            'totalPorBanco' => $totalPorBanco,
            'totalGeral'    => (float) $totalGeral,
        ];
    }



    public static function getIntervaloTotalExtratos(){
        //TODO Where provisório porque tem uns valores errados na tabela. Ex ano que começa com 00
        $sql = '
            SELECT
                MIN(lancamento) AS primeira_data,
                MAX(lancamento) AS ultima_data
            FROM tblextratobancario
            WHERE EXTRACT(YEAR FROM lancamento) >= 1000
        ';

        $data = DB::select($sql);
        return $data[0];
    }
}
