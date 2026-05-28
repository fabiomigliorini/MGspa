<?php

namespace Mg\Pessoa;

use Carbon\Carbon;

use JasperPHP\Instructions;
use JasperPHP\Report;
use JasperPHP\PdfProcessor;


class PessoaComandaVendedorService
{
    public static function pdf (Pessoa $pessoa)
    {
        $data = [new PessoaComandaVendedor($pessoa)];
        $report = new Report(
            app_path('/Mg/Pessoa/comandaVendedor.jrxml'),
            [],
            null,
            false,
            ['type' => 'array', 'data' => $data],
        );
        Instructions::prepare($report);
        $report->generate();
        $report->out();
        $pdfProcessor = PdfProcessor::get();
        $pdf = $pdfProcessor->Output('comanda.pdf', 'S');
        return $pdf;
    }

    public static function imprimir (Pessoa $pessoa, $impressora, $copias)
    {
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "' . env('ABLY_APP_KEY') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora . '", "data": "{\"url\": \"' . env('APP_URL') . 'api/v1/pessoa/' . $pessoa->codpessoa . '/comanda-vendedor\", \"method\": \"get\", \"options\": [\"fit-to-page\"], \"copies\": ' . $copias . '}" }\'';
        exec($cmd);
    }

}
