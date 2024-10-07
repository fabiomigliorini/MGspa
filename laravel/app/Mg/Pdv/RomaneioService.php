<?php

namespace Mg\Pdv;

use Dompdf\Dompdf;
use Mg\Negocio\Negocio;

class RomaneioService
{
    public static function pdf(Negocio $negocio, bool $confissao = true)
    {
        // pega os anexos em base64
        $anexos = PdvAnexoService::base64($negocio->codnegocio, true);

        // carrega HTML da view
        $dompdf = new Dompdf();
        $html = view('negocio.romaneio', compact('negocio', 'anexos', 'confissao'))->render();
        $dompdf->loadHtml($html);

        // Bobina 80mm x 297 (altura A4)
        $dompdf->setPaper([0.0, 0.0, 226.77, 841.89], 'portrait');

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
            . $codnegocio . '/romaneio\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);
    }
}
