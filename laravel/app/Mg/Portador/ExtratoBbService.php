<?php

namespace App\Mg\Portador;

use Exception;
use Carbon\Carbon;
use Mg\Portador\ExtratoBancario;
use Mg\Portador\Portador;
use Mg\Portador\PortadorSaldo;

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
            if (!$extrato) {
                throw new Exception("Erro ao consultar extrato na API");
            }
            if (isset($extrato['erros'])) {
                throw new Exception($extrato['erros'][0]['mensagem'], 1);
            }

            //TODO: Tratar possíveis erros da api
            // if (!isset($extrato['listaLancamento'])) {
            //     dd($extrato);
            //     continue;
            // }
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

                //Salva o saldo aqui
                $codPortador = $portador->codportador;
                $dataLancamento = Carbon::createFromFormat('dmY', $lancamento['dataLancamento']);
                $codHistorico = $lancamento['codigoHistorico'];
                $textoDescricaoHistorico = $lancamento['textoDescricaoHistorico'];
                $valorLancamento = $lancamento['valorLancamento'];

                if($codHistorico == '0'){
                    if($textoDescricaoHistorico == 'Saldo do dia'){
                        //Código de histórico 0 – traz o saldo imediatamente anterior ao período pesquisado,
                        $portadorSaldo = PortadorSaldo::firstOrNew([
                            'codportador' => $codPortador,
                            'dia' => $dataLancamento
                        ]);

                        $portadorSaldo->saldobancario = $valorLancamento;

                        if (!$portadorSaldo->save()) {
                            $falhas++;
                        };
                        $registros++;
                    }else{
                        //e/ou o(s) saldo(s) parcial(is) dos dias ao longo do período pesquisado
                        //Saldo anterior, não fazer nada.
                    }

                }else if($codHistorico == '999'){
                    //Código de histórico 999 – traz o saldo final do período pesquisado
                    //Não fazer nada
                }else{
                    //Talvez criar a entrada de $extratoBancario somente aqui dentro para o saldo não aparecer em extratos
                    $extratoBancario = ExtratoBancario::firstOrNew([
                        'codportador' => $codPortador,
                        'fitid' => $lancamento['numeroDocumento'],
                    ]);

                    //$extratoBancario->codextratobancariotipomovimento = $tipo->codextratobancariotipomovimento;
                    $extratoBancario->indicadortipolancamento = $lancamento['indicadorTipoLancamento'];
                    $extratoBancario->lancamento = $dataLancamento;
                    if ($lancamento['dataMovimento'] != '0') {
                        $extratoBancario->movimento = Carbon::createFromFormat('dmY', $lancamento['dataMovimento']);
                    }
                    $extratoBancario->codigoagenciaorigem = $lancamento['codigoAgenciaOrigem'];
                    $extratoBancario->numerolote = $lancamento['numeroLote'];
                    //$extratoBancario->numeroDocumento = $lancamento['numeroDocumento'];
                    $extratoBancario->codigohistorico = $codHistorico;
                    $extratoBancario->textodescricaohistorico = $textoDescricaoHistorico;
                    $extratoBancario->valor = $valorLancamento;
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
            }

            $numeroPaginaSolicitacao = $extrato['numeroPaginaProximo'];
        } while ($numeroPaginaSolicitacao != 0);
        return [
            'codportador' => $portador->codportador,
            'portador' => $portador->portador,
            'inicio' => $dataInicioSolicitacao->format('Y-m-d'),
            'fim' => $dataFimSolicitacao->format('Y-m-d'),
            'registros' => $registros,
            'falhas' => $falhas,
        ];
    }

    public static function listaExtratos($codportador, $dataInicial, $dataFinal, $per_page){
        $extratosPage = ExtratoBancario::where('codportador', '=', $codportador)
            ->whereBetween('lancamento', [$dataInicial, $dataFinal])
            ->orderBy('criacao', 'desc')
            ->paginate($per_page);

        return $extratosPage;
    }
}