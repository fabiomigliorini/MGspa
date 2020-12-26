<?php

namespace Mg\Pix;

use Carbon\Carbon;

use Mg\NaturezaOperacao\Operacao;
use Mg\Negocio\Negocio;
use Mg\Pix\GerenciaNet\GerenciaNetService;

class PixService
{
    public static function criarPixCobNegocio (Negocio $negocio)
    {
        // Valida se é de saída
        if ($negocio->NaturezaOperacao->codoperacao != Operacao::SAIDA) {
            throw new \Exception("Operação não é de saída!", 1);
        }

        // calcula saldo a pagar do negocio
        $pago = $negocio->NegocioFormaPagamentoS()->sum('valorpagamento');
        $saldo = $negocio->valortotal - $pago;
        if ($saldo <= 0) {
            throw new \Exception("Não existe saldo à pagar para gerar o PIX!", 1);
        }

        // procura ou cria registro
        $cob = new PixCob([
            'codnegocio' => $negocio->codnegocio,
            'valororiginal' => $saldo
        ]);

        // 3 dias = 3 * 24 * 60 * 60
        $cob->expiracao = 259200;

        // Status NOVA
        $status = PixStatus::firstOrCreate([
            'pixstatus' => 'NOVA'
        ]);
        $cob->codpixstatus = $status->codpixstatus;

        // CNPJ ou CPF
        if (!empty($negocio->Pessoa->cnpj)) {
            $cob->nome = $negocio->Pessoa->pessoa;
            if ($negocio->Pessoa->fisica) {
                $cob->cpf = $negocio->Pessoa->cnpj;
            } else {
                $cob->cnpj = $negocio->Pessoa->cnpj;
            }
        // } elseif (!empty($negocio->cpf)) {
            // $cob->cpf = $negocio->cpf;
        }

        // Texto para ser apresentado pro cliente
        $codnegocio = str_pad($negocio->codnegocio, 8, '0', STR_PAD_LEFT);
        $cob->solicitacaopagador = "MG Papelaria! Pagamento referente negócio #{$codnegocio}!";

        // Portador Hardcoded por enquanto
        $cob->codportador = env('PIX_GERENCIANET_CODPORTADOR');
        $cob->save();

        $cob->txid = 'PIXCOB' . str_pad($cob->codpixcob, 29, '0', STR_PAD_LEFT);
        $cob->save();

        return $cob;
    }

    public static function transmitirPixCob(PixCob $cob)
    {
        if (empty($cob->Portador->pixdict)) {
            throw new \Exception("Não existe Chave PIX DICT cadastrada para o portador!", 1);
        }
        if ($cob->Portador->Banco->numerobanco == 364) {
            return GerenciaNetService::transmitirPixCob($cob);
        }
        throw new \Exception("Sem integração definida para o Banco {$cob->Portador->Banco->numerobanco}!", 1);
    }

    public static function consultarPixCob(PixCob $cob)
    {
        if (empty($cob->Portador->pixdict)) {
            throw new \Exception("Não existe Chave PIX DICT cadastrada para o portador!", 1);
        }
        if ($cob->Portador->Banco->numerobanco == 364) {
            return GerenciaNetService::consultarPixCob($cob);
        }
        throw new \Exception("Sem integração definida para o Banco {$cob->Portador->Banco->numerobanco}!", 1);
    }
}
