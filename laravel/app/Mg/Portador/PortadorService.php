<?php

namespace Mg\Portador;

use Illuminate\Support\Facades\DB;
use Mg\Banco\Banco;
use OfxParser\Parser;

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
        $ofxParser = new Parser();
        $ofx = $ofxParser->loadFromString($ofxString);

        // Localiza o Portador
        $bankAccount = reset($ofx->bankAccounts);
        $portador = static::buscarPortadorOfx($bankAccount->routingNumber, $bankAccount->accountNumber);

        // Get the statement transactions for the account
        $transactions = $bankAccount->statement->transactions;
        $resultado = self::salvaTransacoes($transactions, $portador);
        self::salvaSaldos($transactions, $bankAccount, $portador);

        return $resultado;
    }

    private static function salvaTransacoes($transactions, $portador)
    {
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

    private static function salvaSaldos($transactions, $bankAccount, $portador)
    {
        usort($transactions, function($a, $b) {
            return $b->date <=> $a->date;
        });

        // Saldo final e data
        $saldoFinal = (float)$bankAccount->balance;
        $totalPorDia = array();

        foreach ($transactions as $transaction) {
            $dateKey = $transaction->date->format("Y-m-d");

            if(!isset($totalPorDia[$dateKey])){
                $totalPorDia[$dateKey] = 0;
            }
            $totalPorDia[$dateKey] += $transaction->amount;
        }

        //$saldos = array();
        $proximoSaldo = $saldoFinal;
        foreach ($totalPorDia as $dia => $valor) {
            //$saldos[$dia] = $proximoSaldo;
            $saldo = PortadorSaldo::firstOrNew([
                'codportador' => $portador->codportador,
                'dia' => $dia,
            ]);
            $saldo->saldobancario = $proximoSaldo;
            $saldo->save();

            $proximoSaldo -= $valor;
        }

        //dd($saldos);
    }

    public static function listaSaldos($dia)
    {
        $sql = "
            select
                p.codfilial,
                f.filial,
                b.codbanco,
                b.banco,
                p.codportador,
                p.portador,
                s.saldobancario
            from tblportador p
                 inner join tblbanco b on (b.codbanco = p.codbanco)
                 left join tblfilial f on (f.codfilial = p.codfilial)
                 left join tblportadorsaldo s on (s.codportadorsaldo =
                      (
                          select us.codportadorsaldo
                          from tblportadorsaldo us
                          where us.codportador = p.codportador
                            and us.dia <= :dia
                          order by us.dia desc
                          limit 1
                      )
                )
            where coalesce(p.inativo, now()) >= :dia
            order by p.codfilial, p.codbanco, p.codportador
        ";

        $data = DB::select($sql, [
            'dia' => $dia
        ]);

        //dd($data);

        return self::montaEstruturaSaldo($data);
        //return $data;
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
                        'saldobancario'       => (float) $registro->saldobancario,
                    ];
                })
                ->values()
                ->all();
        };

        // soma os saldos de um único banco
        $somarBanco = function($itensBanco) use ($montarPortadores): float {
            $portadores = $montarPortadores($itensBanco);
            return array_sum(array_column($portadores, 'saldobancario'));
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
                //dd($itensFilial);
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
                    'saldobancario'
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

    public static function listaMovimentacoes($codportador, $dataInicial, $dataFinal){
        $extratosPage = ExtratoBancario::where('codportador', '=', $codportador)
            ->whereBetween('lancamento', [$dataInicial, $dataFinal])
            ->orderBy('lancamento', 'asc')->get();

        return $extratosPage;
    }

    public static function listaSaldosPortador($codportador, $dataInicial, $dataFinal)
    {
        $saldoAnterior = PortadorSaldo::select(['codportadorsaldo', 'dia', 'saldobancario'])->where('codportador', $codportador)
            ->where('dia', '<', $dataInicial)
            ->orderByDesc('dia')
            ->first();

        $saldos = PortadorSaldo::select(['codportadorsaldo', 'dia', 'saldobancario'])->where('codportador', '=', $codportador)
                    ->whereBetween('dia', [$dataInicial, $dataFinal])
                    ->orderBy('dia', 'asc')->get();

        return [
            'saldos' => $saldos,
            'saldoAnterior' => $saldoAnterior
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
