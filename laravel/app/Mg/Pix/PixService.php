<?php

namespace Mg\Pix;

use Carbon\Carbon;

use Mg\NaturezaOperacao\Operacao;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Portador\Portador;
use Mg\Pix\GerenciaNet\GerenciaNetService;
use Mg\FormaPagamento\FormaPagamento;

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
        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => 'NOVA'
        ]);
        $cob->codpixcobstatus = $status->codpixcobstatus;

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
            $cob = GerenciaNetService::consultarPixCob($cob);
            return $cob;
        }
        throw new \Exception("Sem integração definida para o Banco {$cob->Portador->Banco->numerobanco}!", 1);
    }

    public static function importarPix(Portador $portador, array $arrPix, PixCob $pixCob = null)
    {
        $pix = Pix::firstOrNew([
            'e2eid' => $arrPix['endToEndId'],
            'codportador' => $portador->codportador,
        ]);
        $pix->txid = $arrPix['txid']??null;
        if (empty($pix->codpixcob) && !empty($pix->txid)) {
            $pixCob = PixCob::where('codportador', $pix->codportador)
                ->where('txid', $pix->txid)->first();
        }
        if (!empty($pixCob)) {
            $pix->codpixcob = $pixCob->codpixcob;
        }
        $pix->valor = $arrPix['valor']??null;
        $pix->horario = Carbon::parse($arrPix['horario']??null);
        if (isset($arrPix['pagador'])) {
            $pix->nome = $arrPix['pagador']['nome']??null;
            $pix->cpf = $arrPix['pagador']['cpf']??null;
            $pix->cnpj = $arrPix['pagador']['cnpj']??null;
        }
        $pix->infopagador = $arrPix['infoPagador']??null;
        $pix->save();

        $arrDevs = $arrPix['devolucoes']??[];
        foreach ($arrDevs as $arrDev) {
            $pixDevolucao = PixDevolucao::firstOrNew([
                'codpix' => $pix->codpix,
                'rtrid' => $arrDev['rtrId']
            ]);
            $pixDevolucao->id = $arrDev['id']??null;
            $pixDevolucao->valor = $arrDev['valor']??null;
            if (!empty($arrDev['horario']['solicitacao'])) {
                $pixDevolucao->solicitacao = Carbon::parse($arrDev['horario']['solicitacao']);
            }
            if (!empty($arrDev['horario']['liquidacao'])) {
                $pixDevolucao->liquidacao = Carbon::parse($arrDev['horario']['liquidacao']);
            }
            $status = PixDevolucaoStatus::firstOrCreate([
                'pixdevolucaostatus' => $arrDev['status']
            ]);
            $pixDevolucao->codpixdevolucaostatus = $status->codpixdevolucaostatus;
            $pixDevolucao->save();
        }

        if (!empty($pix->codpixcob)) {
            static::processarPixCobNegocio($pix->PixCob);
        }
        return $pix;
    }

    public static function processarPixCobNegocio (PixCob $cob)
    {
        if (empty($cob->codnegocio)) {
            return;
        }
        $valorpagamento = $cob->PixS()->sum('valor');
        if ($valorpagamento <= 0) {
            return;
        }
        $nfp = NegocioFormaPagamento::firstOrNew([
            'codpixcob' => $cob->codpixcob
        ]);
        $nfp->codnegocio = $cob->codnegocio;
        $nfp->valorpagamento = $valorpagamento;
        $fp = FormaPagamento::firstOrNew(['pix' => true]);
        if (!$fp->exists) {
            $fp->formapagamento = 'PIX';
            $fp->avista = true;
            $fp->save();
        }
        $nfp->codformapagamento = $fp->codformapagamento;
        $nfp->save();
        $fechado = \Mg\Negocio\NegocioService::fecharSePago($cob->Negocio);
    }

    public static function consultarPix(Portador $portador)
    {
        if (empty($portador->pixdict)) {
            throw new \Exception("Não existe Chave PIX DICT cadastrada para o portador!", 1);
        }
        if ($portador->Banco->numerobanco == 364) {
            $pixRecebidos = GerenciaNetService::consultarPix($portador);
            return $pixRecebidos;
        }
        throw new \Exception("Sem integração definida para o Banco {$portador->Banco->numerobanco}!", 1);
    }

}
