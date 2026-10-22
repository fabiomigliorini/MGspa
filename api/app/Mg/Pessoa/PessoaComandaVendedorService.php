<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Mg\Impressora\ImpressoraService;

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
        ImpressoraService::publicar(ImpressoraService::resolver($impressora), $url, ['fit-to-page'], $copias);
    }

}
