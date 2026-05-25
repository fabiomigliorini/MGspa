<?php

namespace Mg\Pessoa;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Comanda do Vendedor — PDF pequeno (80x60mm) com código da pessoa,
 * fantasia e código de barras "VDD<codpessoa>" para o vendedor usar
 * no caixa.
 *
 * Substitui o antigo serviço baseado em JasperPHP + JRXML por uma
 * implementação direta com Dompdf + Blade + Picqer\Barcode (todos
 * já no composer).
 */
class PessoaComandaVendedorService
{
    public static function pdf(Pessoa $pessoa): string
    {
        $codigoBarras = 'VDD' . str_pad((string) $pessoa->codpessoa, 8, '0', STR_PAD_LEFT);

        // Gera código de barras PNG (Code 128) em base64
        $generator = new BarcodeGeneratorPNG();
        $barcodePng = base64_encode($generator->getBarcode($codigoBarras, $generator::TYPE_CODE_128, 2, 60));

        // Logo (procura arquivo em public/)
        $logoPath = public_path('MGPapelariaLogoPretoBranco.jpeg');
        $logo = is_file($logoPath)
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = View::make('pessoa.comanda-vendedor', [
            'codigo' => '#' . str_pad((string) $pessoa->codpessoa, 8, '0', STR_PAD_LEFT),
            'fantasia' => $pessoa->fantasia,
            'codigoBarras' => $codigoBarras,
            'barcodePng' => $barcodePng,
            'logo' => $logo,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // Papel pequeno (80x60mm) — adequado pra impressora térmica
        $dompdf->setPaper([0, 0, 226.77, 170.08]); // 80mm x 60mm em pt
        $dompdf->render();
        return $dompdf->output();
    }

    public static function imprimir(Pessoa $pessoa, string $impressora, int $copias): string
    {
        // Notifica fila de impressão via Ably (mesmo payload do legacy)
        $payload = json_encode([
            'url' => rtrim((string) env('APP_URL'), '/') . '/api/v1/pessoa/' . $pessoa->codpessoa . '/comanda-vendedor',
            'method' => 'get',
            'options' => ['fit-to-page'],
            'copies' => $copias,
        ]);

        $body = json_encode(['name' => $impressora, 'data' => $payload]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages '
             . '-u ' . escapeshellarg((string) env('ABLY_APP_KEY')) . ' '
             . '-H "Content-Type: application/json" '
             . '--data ' . escapeshellarg($body);
        exec($cmd);

        // Retorna o PDF também (compat com chamadas antigas que esperam binário)
        return self::pdf($pessoa);
    }
}
