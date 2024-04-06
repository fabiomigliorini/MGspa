<?php

namespace Mg\Pdv;

use Dompdf\Dompdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Mg\Negocio\Negocio;
use Mg\Titulo\TituloService;

class ValeService
{
    public static function pdf(Negocio $negocio)
    {
        $generator = new BarcodeGeneratorPNG();
        $tits = [];
        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            if (!empty($nfp->codtitulo)) {
                if ($nfp->Titulo->codtipotitulo == TituloService::TIPO_VALE && $nfp->Titulo->saldo < 0) {
                    $tits[$nfp->codtitulo] = $nfp->Titulo;
                }
            }
            foreach ($nfp->TituloS as $tit) {
                if ($tit->codtipotitulo == TituloService::TIPO_VALE && $tit->saldo < 0) {
                    $tits[] = $tit;
                }
            }
        }

        $barcodes = [];
        foreach ($tits as $tit) {
            $str = 'VALE' . str_pad($tit->codtitulo, 8, '0', STR_PAD_LEFT);
            $barcodes[$tit->codtitulo] = base64_encode($generator->getBarcode($str, $generator::TYPE_CODE_128, 1, 60));
        }

        // carrega HTML da view
        $dompdf = new Dompdf();
        $html = view('negocio.vale', compact('tits', 'barcodes'))->render();
        $dompdf->loadHtml($html);

        // Bobina 80mm x 297 (altura A4)
        $dompdf->setPaper([0.0, 0.0, 226.77, 450], 'portrait');

        // Renderiza
        $dompdf->render();

        // retorna o PDF em uma variavel
        return $dompdf->output();
    }

    public static function imprimir($codnegocio, $impressora)
    {
        // Executa comando de impressao
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "'
            . env('ABLY_APP_KEY') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora
            . '", "data": "{\"url\": \"' . env('APP_URL') . 'api/v1/pdv/negocio/'
            . $codnegocio . '/vale\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);
    }
}
