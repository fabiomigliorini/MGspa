<?php

namespace App\Mg\Portador;

use Carbon\Carbon;
use Mg\Portador\ExtratoBancario;
use Mg\Portador\Portador;

class ExtratoBbService
{

    //TODO: Essa função esta duplicada em BoletoBbService
    public static function verificaTokenValido (Portador $portador)
    {
        if (!empty($portador->bbtokenexpiracao)) {
            if ($portador->bbtokenexpiracao->isFuture()) {
                return $portador->bbtoken;
            }
        }
        $token = ExtratoBbApiService::token($portador);
        $expiracao = Carbon::now()->addSeconds($token['expires_in'] * 0.5);
        $portador->update([
            'bbtoken' => $token['access_token'],
            'bbtokenexpiracao' => $expiracao,
        ]);

        //TODO: Retirar isso depois
        $portador->bbtoken = $token['access_token'];
        $portador->bbtokenexpiracao = $expiracao;

        return $portador->bbtoken;
    }
    public static function consultarExtrato(Portador $portador, $dataInicioSolicitacao, $dataFimSolicitacao)
    {
        $numeroPaginaSolicitacao = 1;
        $registros = 0;
        $falhas = 0;

        do {

            $bbtoken = static::verificaTokenValido($portador);

            $extrato = ExtratoBbApiService::contaCorrente(
                $bbtoken,
                $portador->bbdevappkey,
                $portador->agencia,
                $portador->conta,
                $dataInicioSolicitacao,
                $dataFimSolicitacao,
                $numeroPaginaSolicitacao
            );

            //TODO: Tratar possíveis erros da api

            foreach ($extrato['listaLancamento'] as $lancamento) {


                /* $trntype = null;
                 switch ($lancamento['indicadorSinalLancamento']){
                     case 'C':
                         $trntype = 'CREDIT';
                         break;
                     case 'D':
                         $trntype = 'DEBIT';
                         break;
                     case '*':
                         break;
                 }
                 $tipo = ExtratoBancarioTipoMovimento::firstOrNew([
                     'trntype' => $trntype,
                 ]);*/

                $extratoBancario = ExtratoBancario::where([
                    'codportador' => $portador->codportador,
                    'fitid' => $lancamento['numeroDocumento'],
                ])->first();

                if ($extratoBancario) {
                    continue;
                }
                //TODO: Ver porque não funcionou com findOrNew
                $extratoBancario = new ExtratoBancario();
                $extratoBancario->codportador = $portador->codportador;
                $extratoBancario->fitid = $lancamento['numeroDocumento'];

                //$extratoBancario->codextratobancariotipomovimento = $tipo->codextratobancariotipomovimento;
                $extratoBancario->indicadortipolancamento = $lancamento['indicadorTipoLancamento'];
                $extratoBancario->lancamento = Carbon::createFromFormat('dmY', $lancamento['dataLancamento']);
                if ($lancamento['dataMovimento'] != '0') {
                    $extratoBancario->movimento = Carbon::createFromFormat('dmY', $lancamento['dataMovimento']);
                }
                $extratoBancario->codigoagenciaorigem = $lancamento['codigoAgenciaOrigem'];
                $extratoBancario->numerolote = $lancamento['numeroLote'];
                //$extratoBancario->numeroDocumento = $lancamento['numeroDocumento'];
                $extratoBancario->codigohistorico = $lancamento['codigoHistorico'];
                $extratoBancario->textodescricaohistorico = $lancamento['textoDescricaoHistorico'];
                $extratoBancario->valor = $lancamento['valorLancamento'];
                $extratoBancario->indicadorsinallancamento = $lancamento['indicadorSinalLancamento'];
                $extratoBancario->textoinformacaocomplementar = $lancamento['textoInformacaoComplementar'];
                $extratoBancario->numerocpfcnpjcontrapartida = $lancamento['numeroCpfCnpjContrapartida'];
                $extratoBancario->indicadortipopessoacontrapartida = $lancamento['indicadorTipoPessoaContrapartida'];
                $extratoBancario->codigobancocontrapartida = $lancamento['codigoBancoContrapartida'];
                $extratoBancario->codigoagenciacontrapartida = $lancamento['codigoAgenciaContrapartida'];
                $extratoBancario->numerocontacontrapartida = $lancamento['numeroContaContrapartida'];
                $extratoBancario->textodvcontacontrapartida = $lancamento['textoDvContaContrapartida'];

                if (!$extratoBancario->save()) {
                    $falhas++;
                };

                $registros++;
            }

            $numeroPaginaSolicitacao = $extrato['numeroPaginaProximo'];
        } while ($numeroPaginaSolicitacao != 0);
        return [
            'codportador' => $portador->codportador,
            'portador' => $portador->portador,
            'registros' => $registros,
            'falhas' => $falhas,
        ];
    }
}