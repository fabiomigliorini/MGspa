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
        $report = new Report(app_path('/Mg/Pessoa/comandaVendedor.jrxml'), []);
        Instructions::prepare($report); // prepara o relatorio lendo o arquivo
        $data = [
            new PessoaComandaVendedor($pessoa),
        ];
        $report->dbData = $data; // aqui voce pode construir seu array de boletos em qualquer estrutura incluindo
        $report->generate();                // gera o relatorio
        $report->out();                     // gera o pdf
        $pdfProcessor = PdfProcessor::get();       // extrai o objeto pdf de dentro do report
        $pdf = $pdfProcessor->Output('comanda.pdf', 'S');  // metodo do TCPF para gerar saida para o browser
        return $pdf;
    }

    public static function imprimir (Pessoa $pessoa, $impressora, $copias)
    {
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "' . env('ABLY_APP_KEY') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora . '", "data": "{\"url\": \"' . env('APP_URL') . 'api/v1/pessoa/' . $pessoa->codpessoa . '/comanda-vendedor\", \"method\": \"get\", \"options\": [\"fit-to-page\"], \"copies\": ' . $copias . '}" }\'';
        exec($cmd);
    }

}
