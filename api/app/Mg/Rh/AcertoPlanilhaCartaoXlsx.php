<?php

namespace Mg\Rh;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AcertoPlanilhaCartaoXlsx implements RecargaCartaoDriver
{
    /**
     * Implementação "planilha" do RecargaCartaoDriver — devolve os bytes do
     * .xlsx CPF|Valor. Delega para o gerador estático abaixo (mantido para
     * compatibilidade com AcertoController@planilhaCartao).
     */
    public function gerarRecarga(int $codperiodo, int $codempresa): string
    {
        return static::gerar($codperiodo, $codempresa);
    }

    /**
     * Gera a planilha (XLSX) de reposição de saldo do cartão-benefício de UMA empresa mãe.
     *
     * Valor = acerto REAL efetivado (portador Caixa) — idêntico à seção "Caixa Financeiro"
     * do Relatório Folha. Fonte compartilhada com a prévia via
     * AcertoRelatorioFolhaPdf::linhasCaixaFinanceiro().
     *
     * Layout exigido pela operadora: cabeçalho "CPF" | "Valor", CPF com máscara
     * (###.###.###-##) e valor com vírgula decimal (100,00).
     *
     * @return string bytes do arquivo .xlsx
     */
    public static function gerar(int $codperiodo, int $codempresa): string
    {
        $linhas = AcertoRelatorioFolhaPdf::linhasCaixaFinanceiro($codperiodo, $codempresa);

        \PhpOffice\PhpSpreadsheet\Settings::setLocale('pt_br');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cartao');

        $sheet->setCellValue('A1', 'CPF');
        $sheet->setCellValue('B1', 'Valor');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $linha = 2;
        foreach ($linhas as $l) {
            $cpf = $l->fisica ? formataCpf(sprintf('%.0f', (float) $l->cpf)) : formataCnpj(sprintf('%.0f', (float) $l->cpf));
            $sheet->setCellValueExplicit("A{$linha}", $cpf, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$linha}", number_format((float) $l->valor, 2, ',', ''), DataType::TYPE_STRING);
            $linha++;
        }

        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(14);

        // Captura os bytes do arquivo (sem gravar em disco)
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
}
