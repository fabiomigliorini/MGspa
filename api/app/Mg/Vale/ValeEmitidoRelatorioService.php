<?php

namespace Mg\Vale;

use Mpdf\Mpdf;

class ValeEmitidoRelatorioService
{
    public static function pdf($vales, array $filtros): string
    {
        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'default_font' => 'helvetica',
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle('Vales Compras Emitidos');
        $mpdf->WriteHTML(static::html($vales, $filtros));

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public static function html($vales, array $filtros): string
    {
        return view('vale-modelo.emitidos-relatorio', [
            'vales' => $vales,
            'filtros' => $filtros,
        ])->render();
    }
}
