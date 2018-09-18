<?php

namespace Mg\Estoque\MinimoMaximo;

use DB;
use Carbon\Carbon;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Mg\Marca\Marca;

class ComprasRepository
{

    public static function gerarPlanilhaPedido (Marca $marca)
    {

        $sql = "
          with estoque as (
                  select
                      elpv.codprodutovariacao
                      , sum(es.saldoquantidade) as estoque
                  from tblestoquelocalprodutovariacao elpv
                  inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
                  left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
                  where el.inativo is null
                  group by elpv.codprodutovariacao
          ), chegando as (
                  select
          		pb_nti.codprodutovariacao
          		, sum(cast(coalesce(nti.qcom * coalesce(pe_nti.quantidade, 1), 0) as bigint)) as chegando
                  from tblnfeterceiro nt
                  inner join tblnfeterceiroitem nti on (nt.codnfeterceiro = nti.codnfeterceiro)
                  inner join tblprodutobarra pb_nti on (pb_nti.codprodutobarra = nti.codprodutobarra)
                  left join tblprodutoembalagem pe_nti on (pe_nti.codprodutoembalagem = pb_nti.codprodutoembalagem)
                  where nt.codnotafiscal IS NULL
                  AND (nt.indmanifestacao IS NULL OR nt.indmanifestacao NOT IN (210220, 210240))
                  AND nt.indsituacao = 1
                  AND nt.ignorada = FALSE
                  group by pb_nti.codprodutovariacao
          )
          select
          	p.codproduto
          	, pv.codprodutovariacao
          	, p.produto || coalesce(' | ' || pv.variacao, '') as produto
            , coalesce(pv.referencia, p.referencia) as referencia
            , pv.dataultimacompra
            , pv.custoultimacompra
            , pv.quantidadeultimacompra
          	, pv.estoqueminimo
          	, pv.estoquemaximo
          	, e.estoque
          	, c.chegando
          	, pv.lotecompra
          from tblproduto p
          inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
          left join estoque e on (e.codprodutovariacao = pv.codprodutovariacao)
          left join chegando c on (c.codprodutovariacao = pv.codprodutovariacao)
          where p.codmarca = :codmarca
          and p.inativo is null
          and pv.inativo is null
          and pv.descontinuado is null
          order by p.produto, p.codproduto, pv.variacao, pv.codprodutovariacao
        ";
        $produtos = DB::select($sql, [
          'codmarca' => $marca->codmarca,
        ]);
        foreach ($produtos as $prod) {
          $prod->comprar = null;
          $est = $prod->estoque + $prod->chegando;
          $repor = $prod->estoquemaximo - $est;
          if ($repor <= 0) {
            continue;
          }
          $lotes = $repor / $prod->lotecompra;
          if ($est < $prod->estoqueminimo) {
            $lotes = ceil($lotes);
          } else {
            $lotes = round($lotes, 0);
          }
          $prod->comprar = empty($lotes)?null:$lotes * $prod->lotecompra;
        }

        static::salvarPlanilhaPedido($marca, $produtos);

        $str = "#,#PV,Produto,Ref,Data,Custo,Quant,Min,Max,Est,Cheg,Lote,Comprar\n";
        foreach ($produtos as $prod) {
          $str .= implode(",", [
            $prod->codproduto,
            $prod->codprodutovariacao,
            '"' . $prod->produto . '"',
            $prod->referencia,
            $prod->dataultimacompra,
            $prod->custoultimacompra,
            $prod->quantidadeultimacompra,
            $prod->estoqueminimo,
            $prod->estoquemaximo,
            $prod->estoque,
            $prod->chegando,
            $prod->lotecompra,
            $prod->comprar,
          ]) . "\n";
        }

        return file_put_contents("/tmp/saida.csv", $str);
    }

    public static function salvarPlanilhaPedido (Marca $marca, $produtos)
    {
        $ret = \PhpOffice\PhpSpreadsheet\Settings::setLocale('pt_br');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Liberation Sans');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setShowGridlines(false);

        $linha = 1;


        // "#,#PV,Produto,Ref,Data,Custo,Quant,Min,Max,Est,Cheg,Lote,Comprar\n"
        $sheet->setCellValue("A{$linha}", '#');
        $sheet->setCellValue("B{$linha}", '# Var');
        $sheet->setCellValue("C{$linha}", 'Produto');
        $sheet->setCellValue("D{$linha}", 'Ref');
        $sheet->setCellValue("E{$linha}", 'Data');
        $sheet->setCellValue("F{$linha}", 'Custo');
        $sheet->setCellValue("G{$linha}", 'Quant');
        $sheet->setCellValue("H{$linha}", 'Min');
        $sheet->setCellValue("I{$linha}", 'Max');
        $sheet->setCellValue("J{$linha}", 'Est');
        $sheet->setCellValue("K{$linha}", 'Cheg');
        $sheet->setCellValue("L{$linha}", 'Lote');
        $sheet->setCellValue("M{$linha}", 'Comprar');
        $sheet->setCellValue("N{$linha}", 'Total');

        $linhaTitulo = $linha;

        $linha++;
        $sheet->freezePane("A{$linha}");

        $linhaInicial = $linha;
        foreach ($produtos as $prod) {
          $sheet->setCellValue("A{$linha}", $prod->codproduto);
          $sheet->setCellValue("B{$linha}", $prod->codprodutovariacao);
          $sheet->setCellValue("C{$linha}", $prod->produto);
          $sheet->setCellValueExplicit("D{$linha}", $prod->referencia, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

          $sheet->setCellValue("E{$linha}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($prod->dataultimacompra));

          $sheet->setCellValue("F{$linha}", $prod->custoultimacompra);
          $sheet->setCellValue("G{$linha}", $prod->quantidadeultimacompra);
          $sheet->setCellValue("H{$linha}", $prod->estoqueminimo);
          $sheet->setCellValue("I{$linha}", $prod->estoquemaximo);
          $sheet->setCellValue("J{$linha}", $prod->estoque);
          $sheet->setCellValue("K{$linha}", $prod->chegando);
          $sheet->setCellValue("L{$linha}", $prod->lotecompra);
          $sheet->setCellValue("M{$linha}", $prod->comprar);
          $sheet->setCellValue("N{$linha}", "=M{$linha}*F{$linha}");
          $linha++;
        }

        $linhaFinal = $linha-1;

        $sheet->getStyle("E{$linhaInicial}:E{$linhaFinal}")->getNumberFormat()->setFormatCode('dd/mmm/yy');
        $sheet->getStyle("F{$linhaInicial}:F{$linhaFinal}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("G{$linhaInicial}:M{$linhaFinal}")->getNumberFormat()->setFormatCode('#,##0');

        $linhaTotal = $linha;
        $sheet->setCellValue("N{$linhaTotal}", "=sum(N{$linhaInicial}:N{$linhaFinal})");
        $sheet->mergeCells("A{$linhaTotal}:M{$linhaTotal}");
        $sheet->setCellValue("A{$linhaTotal}", "Total");
        $sheet->getStyle("A{$linhaTotal}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("M{$linhaInicial}:N{$linhaTotal}")->getFont()->setBold(true);
        $sheet->getStyle("N{$linhaInicial}:N{$linhaTotal}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;;@');

        $sheet->getColumnDimension('A')->setWidth(9);
        $sheet->getColumnDimension('B')->setWidth(9);
        $sheet->getColumnDimension('C')->setWidth(65);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(9);
        $sheet->getColumnDimension('G')->setWidth(7);
        $sheet->getColumnDimension('H')->setWidth(7);
        $sheet->getColumnDimension('I')->setWidth(7);
        $sheet->getColumnDimension('J')->setWidth(7);
        $sheet->getColumnDimension('K')->setWidth(7);
        $sheet->getColumnDimension('L')->setWidth(6);
        $sheet->getColumnDimension('M')->setWidth(7);
        $sheet->getColumnDimension('N')->setWidth(9);

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF999999'],
                ],
            ],
        ];

        $sheet->getStyle("A{$linhaTitulo}:N{$linhaTotal}")->applyFromArray($styleArray);

        $sheet->getStyle("E{$linhaInicial}:L{$linhaFinal}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        //$sheet->getStyle("A{$linhaInicial}:N{$linhaFinal}")->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        // $sheet->getStyle("A{$linhaInicial}:N{$linhaFinal}")->getBorders()->allBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        // $sheet->getStyle("A{$linhaInicial}:N{$linhaFinal}")->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        // $sheet->getStyle("A{$linhaInicial}:N{$linhaFinal}")->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        // $sheet->getStyle("A{$linhaInicial}:N{$linhaFinal}")->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        // $sheet->getStyle("A{$linhaInicial}:N{$linhaFinal}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        // $sheet->getStyle("A{$linhaInicial}:N{$linhaFinal}")->getFill()->getStartColor()->setARGB('FFFF0000');

        $writer = new Xlsx($spreadsheet);
        //$dir = "/media/publico/Documentos/Estoque/Compras/Pedidos/";
        $dir = '/tmp/ped/';
        $arquivo = $dir . Carbon::today()->format('Y-m-d') . " - {$marca->marca}.xlsx";
        $v = 0;
        while (file_exists($arquivo)) {
          $v++;
          $arquivo = $dir . Carbon::today()->format('Y-m-d') . " - {$marca->marca} - v{$v}.xlsx";
        }
        $writer->save($arquivo);
    }

}
