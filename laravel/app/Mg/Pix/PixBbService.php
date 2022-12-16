<?php

namespace Mg\Pix;

use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use Mg\Titulo\BoletoBb\BoletoBbService;
use Mg\Portador\Portador;

class PixBbService
{

    public static function transmitirPixCob (PixCob $pixCob)
    {
        $bbtoken = BoletoBbService::verificaTokenValido($pixCob->Portador);
        $dadosPix = PixBbApiService::transmitirPixCob(
            $bbtoken,
            $pixCob->Portador->bbdevappkey,
            $pixCob->Portador->pixdict,
            $pixCob->txid,
            $pixCob->expiracao,
            $pixCob->valororiginal,
            $pixCob->solicitacaopagador,
            $pixCob->nome,
            $pixCob->cpf,
            $pixCob->cnpj
        );
        if (!empty($dadosPix['erros'])) {
            throw new \Exception('API do BB retornou: ' . $dadosPix['erros'][0]['mensagem']);
        }
        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => $dadosPix['status']
        ]);
        $ret = $pixCob->update([
            'location' => $dadosPix['location'],
            'qrcode' => $dadosPix['textoImagemQRcode'],
            'txid' => $dadosPix['txid'],
            // 'locationid' => $dadosPix['loc']['id']??null,
            'codpixcobstatus' => $status->codpixcobstatus
        ]);
        return $pixCob;
    }

    public static function consultarPixCob(PixCob $pixCob)
    {

        $bbtoken = BoletoBbService::verificaTokenValido($pixCob->Portador);
        $dadosPix = PixBbApiService::consultarPixCob(
            $bbtoken,
            $pixCob->Portador->bbdevappkey,
            $pixCob->txid
        );

        if (isset($dadosPix['erros'])) {
            throw new \Exception($dadosPix['erros'][0]['mensagem']);
        }

        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => $dadosPix['status']
        ]);

        $ret = $pixCob->update([
            'location' => $dadosPix['location'],
            'codpixcobstatus' => $status->codpixcobstatus
        ]);

        if (isset($dadosPix['pix'])) {
            foreach ($dadosPix['pix'] as $pix) {
                PixService::importarPix($pixCob->Portador, $pix, $pixCob);
            }
        }

        $pixCob = $pixCob->fresh();
        return $pixCob;
    }

    public static function consultarPix(
        Portador $portador,
        Carbon $inicio = null,
        Carbon $fim = null,
        int $pagina = 0
    ){

        $bbtoken = BoletoBbService::verificaTokenValido($portador);

        $strInicio = null;
        if (!empty($inicio)) {
            $strInicio = $inicio->toIso8601String();
        }
        $strFim = null;
        if (!empty($fim)) {
            $strFim = $fim->toIso8601String();
        }
        $ret = PixBbApiService::consultarPix(
            $bbtoken,
            $portador->bbdevappkey,
            $strInicio,
            $strFim,
            $pagina
        );

        if (isset($ret['erros'][0])) {
            throw new \Exception($ret['erros'][0]['mensagem'], 1);
        }

        $processados = collect([]);
        if (isset($ret['pix'])) {
            foreach ($ret['pix'] as $item) {
                $processados[] = PixService::importarPix($portador, $item);
            }
        }

        $ret['processados'] = $processados;
        return $ret;
    }



}
