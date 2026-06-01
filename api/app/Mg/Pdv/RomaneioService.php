<?php

namespace Mg\Pdv;

use Dompdf\Dompdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\Common\EccLevel;
use Mg\Negocio\Negocio;

class RomaneioService
{
    public static function pdf(Negocio $negocio, bool $confissao = true)
    {
        // pega os anexos em base64
        $anexos = PdvAnexoService::base64($negocio->codnegocio, true);

        // gera o QR code da confissao (codnegocio + valor) para leitura automatica
        $qrcode = $confissao ? static::qrCodeConfissao($negocio) : null;

        // carrega HTML da view
        $dompdf = new Dompdf();
        $html = view('negocio.romaneio', compact('negocio', 'anexos', 'confissao', 'qrcode'))->render();
        $dompdf->loadHtml($html, 'UTF-8');

        // Bobina 80mm x 297 (altura A4)
        $dompdf->setPaper([0.0, 0.0, 226.77, 841.89], 'portrait');

        // Renderiza
        $dompdf->render();

        // retorna o PDF em uma variavel
        return $dompdf->output();
    }

    // Gera o QR code que vai impresso na confissao. O conteudo
    // "MGCONF|codnegocio|valortotal" é lido de volta pelo PdvAnexoService::sugerir()
    // quando a confissao assinada é fotografada (muito mais robusto que OCR).
    public static function qrCodeConfissao(Negocio $negocio)
    {
        $conteudo = 'MGCONF|' . $negocio->codnegocio . '|' . number_format($negocio->valortotal, 2, '.', '');

        $options = new QROptions([
            'outputType'   => QROutputInterface::GDIMAGE_PNG,
            'eccLevel'     => EccLevel::M,
            'scale'        => 4,
            'outputBase64' => true,
        ]);

        // ja retorna como data:image/png;base64,... pronto pra usar em <img src>
        return (new QRCode($options))->render($conteudo);
    }

    public static function imprimir($codnegocio, $impressora)
    {
        // Executa comando de impressao
        $url = \URL::temporarySignedRoute('pdv.negocio.romaneio', now()->addMinutes(10), ['codnegocio' => $codnegocio]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "'
            . env('ABLY_APP_KEY') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora
            . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);
    }
}
