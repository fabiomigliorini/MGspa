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
        //  dd($dataInicioSolicitacao, $dataFimSolicitacao);
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
                //Doc: https://apoio.developers.bb.com.br/referency/post/647f7847de39c800131d84ad
                if($lancamento['codigoHistorico'] == '0'){
                    //Código de histórico 0 – traz o saldo imediatamente anterior ao período pesquisado,
                    //e/ou o(s) saldo(s) parcial(is) dos dias ao longo do período pesquisado
                    if($lancamento['textoDescricaoHistorico'] == 'Saldo do dia'){
                        if (!self::salvaSaldo($portador->codportador, $lancamento)) {
                            $falhas++;
                        };
                        $registros++;
                    }else{
                        //Saldo anterior, não fazer nada.
                    }
                }else if($lancamento['codigoHistorico'] == '999'){
                    //Código de histórico 999 – traz o saldo final do período pesquisado
                    //Não fazer nada
                }else{
                    if (!self::salvaExtrato($portador->codportador, $lancamento)) {
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

    public static function salvaSaldo($codPortador, $lancamento)
    {
        $portadorSaldo = PortadorSaldo::firstOrNew([
            'codportador' => $codPortador,
            'dia' => Carbon::createFromFormat('dmY', $lancamento['dataLancamento'])
        ]);

        $portadorSaldo->saldobancario = $lancamento['valorLancamento'];

        return $portadorSaldo->save();
    }
    public static function salvaExtrato($codPortador, $lancamento)
    {
        $extratoBancario = ExtratoBancario::firstOrNew([
            'codportador' => $codPortador,
            'fitid' => $lancamento['numeroDocumento'],
        ]);

        //$extratoBancario->codextratobancariotipomovimento = $tipo->codextratobancariotipomovimento;
        $extratoBancario->indicadortipolancamento = $lancamento['indicadorTipoLancamento'];
        $extratoBancario->dia = Carbon::createFromFormat('dmY', $lancamento['dataLancamento']);;
        if ($lancamento['dataMovimento'] != '0') {
            $extratoBancario->movimento = Carbon::createFromFormat('dmY', $lancamento['dataMovimento']);
        }
        $extratoBancario->codigoagenciaorigem = $lancamento['codigoAgenciaOrigem'];
        $extratoBancario->numero = $lancamento['numeroLote'];
        $extratoBancario->codigohistorico = $lancamento['codigoHistorico'];;
        $extratoBancario->observacoes = $lancamento['textoDescricaoHistorico'];
        $extratoBancario->valor = $lancamento['valorLancamento'];
        $extratoBancario->indicadorsinallancamento = $lancamento['indicadorSinalLancamento'];
        $extratoBancario->textoinformacaocomplementar = $lancamento['textoInformacaoComplementar'];
        $extratoBancario->numerocpfcnpjcontrapartida = $lancamento['numeroCpfCnpjContrapartida'];
        $extratoBancario->indicadortipopessoacontrapartida = $lancamento['indicadorTipoPessoaContrapartida'];
        $extratoBancario->codigobancocontrapartida = $lancamento['codigoBancoContrapartida'];
        $extratoBancario->codigoagenciacontrapartida = $lancamento['codigoAgenciaContrapartida'];
        $extratoBancario->numerocontacontrapartida = $lancamento['numeroContaContrapartida'];
        $extratoBancario->textodvcontacontrapartida = $lancamento['textoDvContaContrapartida'];

        return $extratoBancario->save();
    }


}