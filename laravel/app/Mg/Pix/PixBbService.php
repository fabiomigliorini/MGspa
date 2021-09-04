<?php

namespace Mg\Pix;

use Illuminate\Support\Facades\Log;

use Mg\Titulo\BoletoBb\BoletoBbService;

// use DB;
// use Carbon\Carbon;
//
// use Dompdf\Dompdf;
//
// use OpenBoleto\Banco\BancoDoBrasil;
// use OpenBoleto\Agente;
//
// use JasperPHP\Instructions;
// use JasperPHP\Report;
// use JasperPHP\PdfProcessor;
//
// use Mg\Titulo\Titulo;
// use Mg\Titulo\TituloBoleto;
// use Mg\Titulo\MovimentoTitulo;
// use Mg\Titulo\TipoMovimentoTitulo;
// use Mg\Portador\Portador;
// use Mg\Negocio\Negocio;

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



}
