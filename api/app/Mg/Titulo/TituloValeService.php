<?php

namespace Mg\Titulo;

use Dompdf\Dompdf;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * O vale de credito impresso (Vale Compras / Contra Vale), desacoplado da venda (TASK-216): sai do
 * titulo de credito com saldo — vale compras, adiantamento e credito do cliente, qualquer titulo
 * que o PDV aceita como pagamento. Um vale por titulo, nunca somando saldos (o caixa bipa um
 * titulo so'), o mesmo papel na venda (PDV), na tela do titulo e na do pagamento. O codigo de
 * barras VAL{codtitulo} e' o que o caixa bipa no Receber; o cabecalho e os itens, o blade decide
 * pelo titulo (titulo/vale.blade.php).
 */
class TituloValeService
{
    // os titulos de credito com saldo, na ordem pedida (um vale por titulo)
    public static function titulos(array $codtitulos): Collection
    {
        $titulos = Titulo::with([
            'Filial.Pessoa',
            'Pessoa.Cidade.Estado',
            'NegocioVale.PessoaFavorecido',
        ])->whereIn('codtitulo', $codtitulos)->get()->keyBy('codtitulo');
        return collect($codtitulos)->map(function ($cod) use ($titulos) {
            $tit = $titulos[$cod] ?? abort(404, 'Título não encontrado!');
            if ((float) $tit->saldo >= 0) {
                abort(422, "O título {$tit->numero} não tem saldo de crédito: não há vale para imprimir.");
            }
            return $tit;
        })->values();
    }

    public static function pdfTitulos(array $codtitulos): string
    {
        return static::pdf(static::titulos($codtitulos));
    }

    // bobina 80mm; os titulos vem daqui ou da venda (Pdv\ValeService::comprovantes). O blade decide,
    // titulo a titulo, o cabecalho (Contra Vale / Vale Compras / Adiantamento) e se lista os itens
    public static function pdf(Collection $titulos): string
    {
        $generator = new BarcodeGeneratorPNG();
        $barcodes = [];
        foreach ($titulos as $tit) {
            $str = 'VAL' . str_pad($tit->codtitulo, 8, '0', STR_PAD_LEFT);
            $barcodes[$tit->codtitulo] = base64_encode($generator->getBarcode($str, $generator::TYPE_CODE_128, 1, 60));
        }
        $dompdf = new Dompdf();
        $dompdf->loadHtml(view('titulo.vale', compact('titulos', 'barcodes'))->render(), 'UTF-8');
        // Bobina 80mm x 297 (altura A4)
        $dompdf->setPaper([0.0, 0.0, 226.77, 841.89], 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    // manda para a termica pela rota assinada do vale do titulo
    public static function imprimir(int $codtitulo, string $impressora): void
    {
        static::titulos([$codtitulo]);
        $url = \URL::temporarySignedRoute('titulo.vale', now()->addMinutes(10), ['codtitulo' => $codtitulo]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "'
            . config('services.ably.key') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora
            . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);
    }
}
