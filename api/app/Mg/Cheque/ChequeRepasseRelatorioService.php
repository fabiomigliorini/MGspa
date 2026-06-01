<?php

namespace Mg\Cheque;

use Mpdf\Mpdf;

class ChequeRepasseRelatorioService
{
    public static function pdf(int $codchequerepasse): string
    {
        $repasse = ChequeRepasse::with([
            'Portador',
            'ChequeRepasseChequeS.Cheque.Banco',
            'ChequeRepasseChequeS.Cheque.Pessoa',
            'ChequeRepasseChequeS.Cheque.ChequeEmitenteS',
        ])->findOrFail($codchequerepasse);

        $html = view('cheque-repasse.bordero', compact('repasse'))->render();

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_header' => 5,
            'margin_footer' => 5,
            'default_font' => 'helvetica',
            'tempDir' => $tempDir,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
