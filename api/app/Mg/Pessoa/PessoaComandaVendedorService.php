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
        $url = \URL::temporarySignedRoute('pessoa.comanda-vendedor', now()->addMinutes(10), ['codpessoa' => $pessoa->codpessoa]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "' . env('ABLY_APP_KEY') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [\"fit-to-page\"], \"copies\": ' . $copias . '}" }\'';
        exec($cmd);
    }

}
