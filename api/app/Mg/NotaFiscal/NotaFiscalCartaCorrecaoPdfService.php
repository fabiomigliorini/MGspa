<?php

namespace Mg\NotaFiscal;

use Dompdf\Dompdf;

class NotaFiscalCartaCorrecaoPdfService
{
    public static function pdf(NotaFiscal $notaFiscal, NotaFiscalCartaCorrecao $cartaCorrecao)
    {
        $filial = $notaFiscal->Filial;

        $dompdf = new Dompdf();
        $html = view('notafiscal.carta-correcao', compact('notaFiscal', 'cartaCorrecao', 'filial'))->render();
        $dompdf->loadHtml($html);

        // Papel A4
        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        return $dompdf->output();
    }
}
