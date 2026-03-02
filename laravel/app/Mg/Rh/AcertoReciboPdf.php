<?php

namespace Mg\Rh;

use Dompdf\Dompdf;
use Mg\Titulo\LiquidacaoTitulo;

class AcertoReciboPdf
{
    public static function gerar(int $codperiodo, array $colaboradores = []): string
    {
        $query = LiquidacaoTitulo::where('codperiodo', $codperiodo)
            ->whereNull('estornado')
            ->with([
                'MovimentoTituloS.Titulo.Filial.Pessoa.Cidade.Estado',
                'MovimentoTituloS.Titulo.PeriodoColaboradorS.ColaboradorRubricaS',
                'Pessoa.Cidade.Estado',
                'UsuarioCriacao',
            ]);

        if (!empty($colaboradores)) {
            $codpessoas = PeriodoColaborador::whereIn('codperiodocolaborador', $colaboradores)
                ->with('Colaborador')
                ->get()
                ->pluck('Colaborador.codpessoa')
                ->filter()
                ->toArray();
            $query->whereIn('codpessoa', $codpessoas);
        }

        $liquidacoes = $query->get();

        $html = '';
        foreach ($liquidacoes as $liq) {
            if ($liq->credito > 0) {
                $html .= view('liquidacao-titulo.recibo-recebimento', compact('liq'))->render();
            }
            if ($liq->debito > 0) {
                $html .= view('liquidacao-titulo.recibo-pagamento', compact('liq'))->render();
            }
        }

        if (empty($html)) {
            return '';
        }

        // Envolver o HTML com container para layout em 2 colunas
        $wrappedHtml = <<<EOD
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .recibos-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12mm;
            column-gap: 12mm;
        }
        .recibos-container .recibo-outer {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        @media print {
            .recibos-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12mm;
            }
        }
    </style>
</head>
<body>
    <div class="recibos-container">
EOD;
        
        $wrappedHtml .= $html;
        
        $wrappedHtml .= <<<EOD
</body>
</html>
EOD;

        $dompdf = new Dompdf();
        $dompdf->loadHtml($wrappedHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
