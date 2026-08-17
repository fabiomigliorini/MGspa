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
     * compatibilidade com BeeRecargaController@planilha).
     */
    public function gerarRecarga(BeeRecarga $recarga): string
    {
        return static::gerar($recarga->codbeerecarga);
    }

    /**
     * Gera a planilha (XLSX) de reposição de saldo de UM lote de recarga.
     *
     * Lê os ITENS gravados do lote (BeeRecargaService::linhasDaPlanilha), nunca
     * o acerto atual: um arquivo rebaixado meses depois reproduz exatamente o
     * que foi enviado à operadora. É a versão agrupada por CPF — quem tem dois
     * vínculos sai numa linha só, porque a operadora recusa CPF repetido.
     *
     * Layout exigido pela operadora: cabeçalho "CPF" | "Valor", CPF com máscara
     * (###.###.###-##). O valor vai como NÚMERO, com formato de exibição
     * pt-BR — como texto ("100,00") o Excel não somava a coluna.
     *
     * @return string bytes do arquivo .xlsx
     */
    public static function gerar(int $codbeerecarga): string
    {
        $linhas = BeeRecargaService::linhasDaPlanilha($codbeerecarga);

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
            $sheet->setCellValue("B{$linha}", (float) $l->valor);
            $linha++;
        }

        // Célula numérica + máscara: soma no Excel e continua exibindo 1.234,56.
        if ($linha > 2) {
            $ultima = $linha - 1;
            $sheet->getStyle("B2:B{$ultima}")->getNumberFormat()->setFormatCode('#,##0.00');
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
