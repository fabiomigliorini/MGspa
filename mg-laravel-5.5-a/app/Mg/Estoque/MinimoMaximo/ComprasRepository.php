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
            , pv.descontinuado
          from tblproduto p
          inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
          left join estoque e on (e.codprodutovariacao = pv.codprodutovariacao)
          left join chegando c on (c.codprodutovariacao = pv.codprodutovariacao)
          where p.codmarca = :codmarca
          and p.inativo is null
          and pv.inativo is null
          order by p.produto, p.codproduto, pv.variacao, pv.codprodutovariacao
        ";
        $produtos = DB::select($sql, [
          'codmarca' => $marca->codmarca,
        ]);
        foreach ($produtos as $prod) {
          $prod->comprar = null;
          if (!empty($prod->descontinuado)) {
            continue;
          }
          $est = $prod->estoque + $prod->chegando;
          $repor = $prod->estoquemaximo - $est;
          if ($repor <= 0) {
            continue;
          }
          $lote = $prod->lotecompra??1;
          $lotes = $repor / $lote;
          if ($est < $prod->estoqueminimo) {
            $lotes = ceil($lotes);
          } else {
            $lotes = round($lotes, 0);
          }
          $prod->comprar = empty($lotes)?null:$lotes * $lote;
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
        $spreadsheet->getDefaultStyle()->getFont()
            ->setName('Liberation Sans')
            ->setSize(10)
            ;
        $titulo = "Pedido {$marca->marca}";
        $spreadsheet->getProperties()
            ->setCreator("MG Papelaria")
            ->setLastModifiedBy("MG Papelaria")
            ->setTitle($titulo)
            ->setSubject($titulo)
            ->setDescription($titulo)
            ->setKeywords($marca->marca)
            ->setCategory("Pedido");

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($titulo);

        $linha = 1;

        $linhaTitulo = $linha;
        $titulo = "Pedido - {$marca->marca} - " . Carbon::now()->format('d/m/Y');
        $sheet->setCellValue("A{$linhaTitulo}", $titulo);
        $sheet->mergeCells("A{$linhaTitulo}:N{$linhaTitulo}");
        $sheet->getStyle("A{$linhaTitulo}")->getFont()->setSize(20);
        $sheet->getStyle("A{$linhaTitulo}")->getFont()->setBold(true);
        $sheet->getStyle("A{$linhaTitulo}")->getFont()->getColor()->setARGB('FFFF3333');
        $sheet->getRowDimension($linhaTitulo)->setRowHeight(25);
        $sheet->getStyle("A{$linhaTitulo}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$linhaTitulo}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("A{$linhaTitulo}")->getFill()->getStartColor()->setARGB("FFFFFF00");
        $linha++;

        $linhaSubTitulo = $linha;
        $titulo = "MG Papelaria - Migliorini & Migliorini Ltda - 04.576.775/0001-60";
        $sheet->setCellValue("A{$linhaSubTitulo}", $titulo);
        $sheet->mergeCells("A{$linhaSubTitulo}:N{$linhaSubTitulo}");
        $sheet->getStyle("A{$linhaSubTitulo}")->getFont()->setSize(14);
        $sheet->getStyle("A{$linhaSubTitulo}")->getFont()->setBold(true);
        $sheet->getStyle("A{$linhaSubTitulo}")->getFont()->getColor()->setARGB('FFFF3333');
        $sheet->getRowDimension($linhaSubTitulo)->setRowHeight(20);
        $sheet->getStyle("A{$linhaSubTitulo}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$linhaSubTitulo}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("A{$linhaSubTitulo}")->getFill()->getStartColor()->setARGB("FFFFFF00");
        $linha++;

        $linhaTransportador = $linha;
        $titulo = "Transportadora: ";
        $sheet->setCellValue("A{$linhaTransportador}", $titulo);
        $sheet->mergeCells("A{$linhaTransportador}:N{$linhaTransportador}");
        $sheet->getStyle("A{$linhaTransportador}")->getFont()->setSize(14);
        $sheet->getStyle("A{$linhaTransportador}")->getFont()->setBold(true);
        $sheet->getStyle("A{$linhaTransportador}")->getFont()->getColor()->setARGB('FFFF3333');
        $sheet->getRowDimension($linhaTransportador)->setRowHeight(20);
        $sheet->getStyle("A{$linhaTransportador}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$linhaTransportador}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("A{$linhaTransportador}")->getFill()->getStartColor()->setARGB("FFFFFF00");
        $linha++;
        $linha++;

        $linhaTotal = $linha;
        $linha++;

        // "#,#PV,Produto,Ref,Data,Custo,Quant,Min,Max,Est,Cheg,Lote,Comprar\n"
        $linhaCabecalho = $linha;
        $sheet->setCellValue("A{$linhaCabecalho}", '#');
        $sheet->setCellValue("B{$linhaCabecalho}", '# Var');
        $sheet->setCellValue("C{$linhaCabecalho}", 'Produto');
        $sheet->setCellValue("D{$linhaCabecalho}", 'Ref');
        $sheet->setCellValue("E{$linhaCabecalho}", 'Data');
        $sheet->setCellValue("F{$linhaCabecalho}", 'Custo');
        $sheet->setCellValue("G{$linhaCabecalho}", 'Quant');
        $sheet->setCellValue("H{$linhaCabecalho}", 'Min');
        $sheet->setCellValue("I{$linhaCabecalho}", 'Max');
        $sheet->setCellValue("J{$linhaCabecalho}", 'Est');
        $sheet->setCellValue("K{$linhaCabecalho}", 'Cheg');
        $sheet->setCellValue("L{$linhaCabecalho}", 'Lote');
        $sheet->setCellValue("M{$linhaCabecalho}", 'Compra');
        $sheet->setCellValue("N{$linhaCabecalho}", 'Total');
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($linhaCabecalho, $linhaCabecalho);
        $sheet->getStyle("A{$linhaCabecalho}:N{$linhaCabecalho}")->getFont()->setBold(true);
        $sheet->getStyle("A{$linhaCabecalho}:N{$linhaCabecalho}")->getFont()->getColor()->setARGB('FF000000');
        $sheet->getStyle("A{$linhaCabecalho}:N{$linhaCabecalho}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$linhaCabecalho}:N{$linhaCabecalho}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("A{$linhaCabecalho}:N{$linhaCabecalho}")->getFill()->getStartColor()->setARGB("FF999999");
        $linha++;

        $sheet->freezePane("A{$linha}");

        $linhaInicial = $linha;
        foreach ($produtos as $prod) {
          $sheet->setCellValue("A{$linha}", $prod->codproduto);
          $sheet->getCell("A{$linha}")->getHyperlink()->setUrl("http://sistema.mgpapelaria.com.br/MGLara/produto/{$prod->codproduto}");
          $sheet->setCellValue("B{$linha}", $prod->codprodutovariacao);
          $sheet->getCell("B{$linha}")->getHyperlink()->setUrl("http://sistema.mgpapelaria.com.br/MGLara/produto/{$prod->codproduto}");

          $sheet->setCellValue("C{$linha}", $prod->produto);
          if (!empty($prod->descontinuado)) {
            $desc = Carbon::parse($prod->descontinuado)->format('d/m/Y H:i');
            $sheet->getComment("C{$linha}")->getText()->createTextRun("Produto descontinuado em {$desc}");
            $sheet->getStyle("A{$linha}:N{$linha}")->getFont()->getColor()->setARGB('FF808080');
          }


          $sheet->setCellValueExplicit("D{$linha}", $prod->referencia, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
          $sheet->setCellValue("E{$linha}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($prod->dataultimacompra));
          $sheet->setCellValue("F{$linha}", $prod->custoultimacompra);
          $sheet->setCellValue("G{$linha}", $prod->quantidadeultimacompra);
          $sheet->setCellValue("H{$linha}", $prod->estoqueminimo);
          $sheet->setCellValue("I{$linha}", $prod->estoquemaximo);

          $sheet->setCellValue("J{$linha}", $prod->estoque);
          $sheet->setCellValue("K{$linha}", $prod->chegando);

          // Cores do Saldo do Estoque
          $cor = 'FF99FF66'; // Verde
          if (($prod->estoque + $prod->chegando) < $prod->estoqueminimo) {
            $cor = 'FFFF6666'; // Vermelho
          } else if (($prod->estoque + $prod->chegando) > $prod->estoquemaximo) {
            $cor = 'FFFFFF99'; // Amarelo
          }
          $sheet->getStyle("J{$linha}:K{$linha}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
          $sheet->getStyle("J{$linha}:K{$linha}")->getFill()->getStartColor()->setARGB($cor);


          $sheet->setCellValue("L{$linha}", $prod->lotecompra);
          $sheet->setCellValue("M{$linha}", $prod->comprar);
          $sheet->setCellValue("N{$linha}", "=IF(ISNUMBER(M{$linha}),M{$linha}*F{$linha},\"\")");
          $linha++;
        }

        for ($i = 1; $i <= 10; $i++) {
          $sheet->setCellValue("A{$linha}", '');
          $sheet->setCellValue("B{$linha}", '');
          $sheet->setCellValue("C{$linha}", '');
          $sheet->setCellValueExplicit("D{$linha}", '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

          $sheet->setCellValue("E{$linha}", '');

          $sheet->setCellValue("F{$linha}", null);
          $sheet->setCellValue("G{$linha}", '');
          $sheet->setCellValue("H{$linha}", '');
          $sheet->setCellValue("I{$linha}", '');
          $sheet->setCellValue("J{$linha}", '');
          $sheet->setCellValue("K{$linha}", '');
          $sheet->setCellValue("L{$linha}", '');
          $sheet->setCellValue("M{$linha}", null);
          $sheet->setCellValue("N{$linha}", "=M{$linha}*F{$linha}");
          $linha++;
        }

        $linhaFinal = $linha-1;

        // Filtro
        $spreadsheet->getActiveSheet()->setAutoFilter("A{$linhaCabecalho}:N20");

        // Total
        $sheet->mergeCells("A{$linhaTotal}:L{$linhaTotal}");
        $sheet->setCellValue("A{$linhaTotal}", "Total");
        $sheet->getStyle("A{$linhaTotal}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$linhaTotal}:N{$linhaTotal}")->getFont()->setSize(14);
        $sheet->getStyle("A{$linhaTotal}:N{$linhaTotal}")->getFont()->setBold(true);
        $sheet->getRowDimension($linhaTotal)->setRowHeight(20);

        $sheet->mergeCells("M{$linhaTotal}:N{$linhaTotal}");
        $sheet->setCellValue("M{$linhaTotal}", "=subtotal(9,N{$linhaInicial}:N{$linhaFinal})");
        $sheet->getStyle("M{$linhaTotal}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;;@');
        $sheet->getStyle("A{$linhaTotal}:M{$linhaTotal}")->getFont()->setBold(true);


        // Fundo Azul na Quantidade do Pedido e Valor Total
        $sheet->getStyle("M{$linhaInicial}:N{$linhaFinal}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("M{$linhaInicial}:N{$linhaFinal}")->getFill()->getStartColor()->setARGB('FF99FFFF');
        $sheet->getStyle("M{$linhaInicial}:N{$linhaFinal}")->getFont()->setBold(true);

        // Formada Como data Coluna Ultima Compra
        $sheet->getStyle("E{$linhaInicial}:E{$linhaFinal}")->getNumberFormat()->setFormatCode('dd/mmm/yy');

        // Formato Quantidades
        $sheet->getStyle("F{$linhaInicial}:F{$linhaFinal}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("G{$linhaInicial}:M{$linhaFinal}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("N{$linhaInicial}:N{$linhaFinal}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;;@');
        $sheet->getStyle("A{$linhaInicial}:A{$linhaFinal}")->getNumberFormat()->setFormatCode('000000');
        $sheet->getStyle("B{$linhaInicial}:B{$linhaFinal}")->getNumberFormat()->setFormatCode('00000000');

        // Ajusta Largura Colunas
        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(9);
        $sheet->getColumnDimension('C')->setWidth(60);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(9);
        $sheet->getColumnDimension('G')->setWidth(6.5);
        $sheet->getColumnDimension('H')->setWidth(6.5);
        $sheet->getColumnDimension('I')->setWidth(6.5);
        $sheet->getColumnDimension('J')->setWidth(6.5);
        $sheet->getColumnDimension('K')->setWidth(6.5);
        $sheet->getColumnDimension('L')->setWidth(5.5);
        $sheet->getColumnDimension('M')->setWidth(6.5);
        $sheet->getColumnDimension('N')->setWidth(9);

        // Desenha Grade ao redor da tabela
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF999999'],
                ],
            ],
        ];
        $sheet->getStyle("A{$linhaCabecalho}:N{$linhaFinal}")->applyFromArray($styleArray);

        // Alinhamento dos dados Centralizados e no topo da celula
        $sheet->getStyle("A{$linhaInicial}:B{$linhaFinal}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$linhaInicial}:E{$linhaFinal}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G{$linhaInicial}:L{$linhaFinal}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:N{$linhaFinal}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        // Quebra automatica para descricao do produto e referencia
        $sheet->getStyle("C{$linhaInicial}:D{$linhaFinal}")->getAlignment()->setWrapText(true);

        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.3);
        $sheet->getPageMargins()->setRight(0.3);
        $sheet->getPageMargins()->setLeft(0.3);
        $sheet->getPageMargins()->setBottom(1);
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageSetup()->setVerticalCentered(false);
        // $sheet->getHeaderFooter()->setOddHeader('&C&HPlease treat this document as confidential!');
        $sheet->getHeaderFooter()->setOddFooter('&L&B' . $spreadsheet->getProperties()->getTitle() . '&RPágina &P de &N');

        // Gera Arquivo XLSX
        $writer = new Xlsx($spreadsheet);
        // $dir = '/tmp/ped/';
        $dir = "/media/publico/Documentos/Estoque/Compras/Pedidos/";
        $arquivo = $dir . Carbon::today()->format('Y-m-d') . " - {$marca->marca}.xlsx";
        $v = 0;
        while (file_exists($arquivo)) {
          $v++;
          $arquivo = $dir . Carbon::today()->format('Y-m-d') . " - {$marca->marca} - v{$v}.xlsx";
        }
        $writer->save($arquivo);
	chmod ($arquivo, 0666);
    }

}
