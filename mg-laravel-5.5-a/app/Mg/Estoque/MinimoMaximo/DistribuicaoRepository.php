<?php

namespace Mg\Estoque\MinimoMaximo;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Mg\Marca\Marca;
use Mg\Produto\ProdutoVariacao;

use DB;
use Carbon\Carbon;

class DistribuicaoRepository
{
    public static function distribuirSaldoDeposito(Marca $marca)
    {
        $codestoquelocal_deposito = 101001;
        $sql = "
            select
            	p.codproduto
            	, pv.codprodutovariacao
            	, p.produto || coalesce(' | ' || pv.variacao, '') as produto
            	, es.saldoquantidade
            	, elpv.estoquemaximo
            	, elpv.estoqueminimo
            from tblproduto p
            inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
            -- Deposito
            left join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal = :codestoquelocal_deposito and elpv.codprodutovariacao = pv.codprodutovariacao)
            left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            where p.codmarca = :codmarca
            --and p.codproduto = 313196
            --and pv.codprodutovariacao = 81372
            and es.saldoquantidade > 0
            order by p.produto, p.codproduto, pv.variacao, pv.codprodutovariacao
        ";
        $prods = collect(DB::select($sql, [
            'codmarca' => $marca->codmarca,
            'codestoquelocal_deposito' => $codestoquelocal_deposito,
        ]));

        $sql = "
            select
            	el.codestoquelocal
            	, es.saldoquantidade
            	, elpv.estoqueminimo
            	, elpv.estoquemaximo
            	, elpv.estoquemaximo as estoquemaximocalculado
                , 0 as transferir
            from tblestoquelocal el
            inner join tblestoquelocalprodutovariacao elpv on (el.codestoquelocal = elpv.codestoquelocal and elpv.codprodutovariacao = :codprodutovariacao)
            inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            where el.inativo is null
            and el.codestoquelocal != :codestoquelocal_deposito
        ";
        foreach ($prods as $i_prod => $prod) {

            // Busca Variacao
            $pv = ProdutoVariacao::findOrFail($prod->codprodutovariacao);

            // Busca Embalagens e Barras da Variacao
            $barras = collect();
            $embalagens = collect();
            foreach ($pv->ProdutoBarras()->orderBy('barras')->get() as $pb) {
                $barras[$pb->codprodutobarra] = (object) [
                    'codprodutobarra' => $pb->codprodutobarra,
                    'barras' => $pb->barras,
                    'embalagem' => $pb->ProdutoEmbalagem->UnidadeMedida->sigla??null,
                    'quantidade' => $pb->ProdutoEmbalagem->quantidade??null
                ];
                if (!empty($pb->codprodutoembalagem)) {
                    $embalagens[$pb->codprodutoembalagem] = (object) [
                        'codprodutoembalagem' => $pb->codprodutoembalagem,
                        'embalagem' => $pb->ProdutoEmbalagem->UnidadeMedida->sigla??null,
                        'quantidade' => $pb->ProdutoEmbalagem->quantidade??null
                    ];
                }
            }
            $embalagens = $embalagens->sortBy('quantidade');
            $barras = $barras->sortBy('barras')->sortBy('quantidade');
            $prod->barras = $barras;
            $prod->embalagens = $embalagens;

            // Busca Saldo Atual pra Cada Local
            $dests = collect(DB::select($sql, [
                'codprodutovariacao' => $prod->codprodutovariacao,
                'codestoquelocal_deposito' => $codestoquelocal_deposito
            ]));
            $dests = $dests->keyBy('codestoquelocal');

            // Soma saldo total e Maximo Total
            $maximo_total = $dests->sum('estoquemaximo') + $prod->estoquemaximo;
            $saldo_total = $dests->sum('saldoquantidade') + $prod->saldoquantidade;

            // se ha saldo maior que maximo, entao recalcula maximo baseado na quantidade total
            if ($saldo_total > $maximo_total) {

                // Calcula Maximo baseado na quantidade em estoque
                $percentuais = VendaMensalRepository::determinaPercentualEstoqueLocal($pv, $saldo_total);
                $maximos = VendaMensalRepository::ratearQuantidadePelosPercentuais($saldo_total, $percentuais);

                // associa novos maximos aos produtos
                foreach ($maximos as $codestoquelocal => $maximo) {
                    if ($codestoquelocal == $codestoquelocal_deposito) {
                        continue;
                    }
                    if (!isset($dests[$codestoquelocal])) {
                        $dests[$codestoquelocal] = (object)[
                            'codestoquelocal' => $codestoquelocal,
                            'saldoquantidade' => 0,
                            'estoqueminimo' => null,
                            'estoquemaximo' => null,
                            'estoquemaximocalculado' => $maximo,
                            'transferir' => 0
                        ];
                    }
                    $dests[$codestoquelocal]->estoquemaximocalculado = max($maximo, $dests[$codestoquelocal]->estoquemaximo);
                }
            }

            // calcula quantidade para transferir pra cada filial
            $disponivel = $prod->saldoquantidade;
            foreach ($dests as $codestoquelocal => $dest) {
                if ($codestoquelocal == $codestoquelocal_deposito) {
                    continue;
                }
                $dests[$codestoquelocal]->transferir = max($dest->estoquemaximocalculado - $dest->saldoquantidade, 0);
            }

            $prod->transferir = null;
            $prod->saldofinal = null;
            $prod->saldototal = $saldo_total;

            // se o total pra transferir foi maior que o disponivel no deposito
            // calcula proporcionalmente pela quantidade disponivel novos valores
            $transferir_total = $dests->sum('transferir');
            if ($transferir_total > $disponivel) {
                $disponivel_saldo = $disponivel;
                $locais = $dests->count();
                $i = 1;
                foreach ($dests->sortBy('transferir') as $codestoquelocal => $dest) {
                    if ($i == $locais) {
                        $qtd = $disponivel_saldo;
                    } else {
                        $perc = $dest->transferir / $transferir_total;
                        $qtd = round($perc * $disponivel, 0);
                    }
                    $dests[$codestoquelocal]->transferir = $qtd;
                    $disponivel_saldo -= $qtd;
                    $i++;
                }

                // se nao tenta descobrir tamanho lote
            } else {
                $dests = static::definirLoteTransferencia($embalagens, $disponivel, $dests);
            }

            // retorna quantidade para transferir
            $prod->transferir = $dests->sum('transferir');
            $prod->saldofinal = $prod->saldoquantidade - $prod->transferir;
            $prod->destinos = $dests;
        }

        file_put_contents('/tmp/saida.json', $prods->toJson());

        return $prods;
    }

    public static function definirLoteTransferencia($embalagens, $disponivel, $destinos)
    {
        $max = $destinos->sum('estoquemaximocalculado');
        if ($menor_embalagem = $embalagens->sortBy('quantidade')->first()) {
            $lotes_max = $max / $menor_embalagem->quantidade;
        }
        $embalagens = $embalagens->sortByDesc('quantidade');
        $destinos = $destinos->sortByDesc('estoquemaximocalculado');
        foreach ($destinos as $dest) {
            $dest->lotetransferencia = 1;
            foreach ($embalagens as $emb) {

                if (empty($dest->transferir)) {
                    continue;
                }


                // verifica lotes disponiveis no deposito
                $transferir_total = $destinos->sum('transferir');
                $lotes_disponiveis = ($disponivel - $transferir_total) / $emb->quantidade;

                // calcula quantidade de lotes
                $lotes = $dest->transferir / $emb->quantidade;

                // Descarta se lote muito grande pra quantidade da filial
                if ($dest->saldoquantidade > $dest->estoqueminimo && $lotes < 0.3) {
                    continue;
                }

                // desconsidera se menos de meio lote, e quantidade de lotes no deposito menor que 3
                if ($lotes < 0.5 && $lotes_disponiveis <= 2) {
                    continue;
                }

                // arredonda lotes e calcula nova quantidade a transferir
                // $lotes = round($lotes, 0);
                $lotes = max(1, round($lotes, 0));
                $transferir = $lotes * $emb->quantidade;
                $transferir_total += $transferir - $dest->transferir;

                // calcula novo saldo filial
                $saldo = $transferir + $dest->saldoquantidade;

                // se quantidade a transferir menor que estoque minimo, aumenta um lote e recalcula
                if ($saldo <= $dest->estoqueminimo) {
                    $lotes++;
                    $transferir_total = $destinos->sum('transferir');
                    $transferir = $lotes * $emb->quantidade;
                    $transferir_total += $transferir - $dest->transferir;
                }

                // desconsidera se nao tem quantidade suficiente
                if ($transferir_total > $disponivel) {
                    continue;
                }

                // ajusta nova quantidade transferir pelo lote calculado
                $dest->lotetransferencia = $emb->quantidade;
                $dest->transferir = $transferir;
            }

            if ($menor_embalagem) {
                if ($dest->lotetransferencia == 1 && $lotes_max >= 5 &&  $dest->saldoquantidade > $dest->estoqueminimo) {
                    $dest->transferir = 0;
                }
            }
        }

        return $destinos;
    }


    public static function criarPlanilhaDistribuicaoSaldoDeposito(Marca $marca)
    {
        // busca array distribuição dos produtos
        $produtos = static::distribuirSaldoDeposito($marca);

        // inicializa PhpSpreadsheet
        $ret = \PhpOffice\PhpSpreadsheet\Settings::setLocale('pt_br');
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()
            ->setName('Liberation Sans')
            ->setSize(10)
            ;
        $titulo = "Transferência {$marca->marca}";
        $spreadsheet->getProperties()
            ->setCreator("MG Papelaria")
            ->setLastModifiedBy("MG Papelaria")
            ->setTitle($titulo)
            ->setSubject($titulo)
            ->setDescription($titulo)
            ->setKeywords($marca->marca)
            ->setCategory("Transferência");
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($titulo);

        // inicializa controle numeracao de linhas
        $linha = 1;

        // Linha de titulo
        $linhaTitulo = $linha;
        $titulo = "{$marca->marca} - " . Carbon::now()->format('d/m/Y');
        $sheet->setCellValue("A{$linhaTitulo}", $titulo);
        $sheet->mergeCells("A{$linhaTitulo}:H{$linhaTitulo}");
        $sheet->getStyle("A{$linhaTitulo}")->getFont()->setSize(20);
        $sheet->getStyle("A{$linhaTitulo}")->getFont()->setBold(true);
        $sheet->getStyle("A{$linhaTitulo}")->getFont()->getColor()->setARGB('FFFF3333');
        $sheet->getRowDimension($linhaTitulo)->setRowHeight(25);
        $sheet->getStyle("A{$linhaTitulo}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$linhaTitulo}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("A{$linhaTitulo}")->getFill()->getStartColor()->setARGB("FFFFFF00");
        $linha++;
        $linha++;

        // Cabecalho
        $linhaCabecalho = $linha;
        $sheet->setCellValue("A{$linhaCabecalho}", '#');
        $sheet->setCellValue("B{$linhaCabecalho}", 'Produto');
        $sheet->setCellValue("C{$linhaCabecalho}", 'Barras');
        $sheet->setCellValue("D{$linhaCabecalho}", 'Sld');
        $sheet->setCellValue("E{$linhaCabecalho}", 'Bot');
        $sheet->setCellValue("F{$linhaCabecalho}", 'Cen');
        $sheet->setCellValue("G{$linhaCabecalho}", 'Imp');
        $sheet->setCellValue("H{$linhaCabecalho}", 'Dep');
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($linhaCabecalho, $linhaCabecalho);
        $sheet->getStyle("A{$linhaCabecalho}:H{$linhaCabecalho}")->getFont()->setBold(true);
        $sheet->getStyle("A{$linhaCabecalho}:H{$linhaCabecalho}")->getFont()->getColor()->setARGB('FF000000');
        $sheet->getStyle("A{$linhaCabecalho}:H{$linhaCabecalho}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$linhaCabecalho}:H{$linhaCabecalho}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("A{$linhaCabecalho}:H{$linhaCabecalho}")->getFill()->getStartColor()->setARGB("FF999999");
        $linha++;

        // Ajusta Largura Colunas
        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(60);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(6.5);
        $sheet->getColumnDimension('E')->setWidth(6.5);
        $sheet->getColumnDimension('F')->setWidth(6.5);
        $sheet->getColumnDimension('G')->setWidth(6.5);
        $sheet->getColumnDimension('H')->setWidth(6.5);

        // congela paineis
        $sheet->freezePane("A{$linha}");

        // colunas das filiais
        $colunas = [
            102001 => 'E',
            103001 => 'F',
            104001 => 'G',
        ];

        // linha onde comeca os produtos
        $linhaInicial = $linha;

        // percorre produtos
        foreach ($produtos as $prod) {
            $linha2 = $linha + 1;

            // codigo do produto
            $sheet->mergeCells("A{$linha}:A{$linha2}");
            $sheet->setCellValue("A{$linha}", $prod->codproduto);
            $sheet->getCell("A{$linha}")->getHyperlink()->setUrl("http://sistema.mgpapelaria.com.br/MGLara/produto/{$prod->codproduto}");
            $sheet->getStyle("A{$linha}:A{$linha2}")->getNumberFormat()->setFormatCode('000000');

            // Produto
            $sheet->mergeCells("B{$linha}:B{$linha2}");
            $sheet->setCellValue("B{$linha}", $prod->produto);
            $sheet->getStyle("B{$linha}")->getFont()->setBold(true);
            $sheet->getStyle("B{$linha}")->getFont()->setSize(12);

            // Barras
            $sheet->mergeCells("C{$linha}:C{$linha2}");
            $texto = [];
            $linhasBarras = 0;
            foreach ($prod->barras->take(5) as $barras) {
                $str = "{$barras->barras}";
                if (!empty($barras->quantidade)) {
                    $str .= " {$barras->embalagem} C/";
                    $str .= formataNumero($barras->quantidade, 0);
                }
                $texto[] .= $str;
                $linhasBarras++;
            }
            $texto = implode("\n", $texto);
            $sheet->setCellValueExplicit("C{$linha}", $texto, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            // ajusta altura das linhas conforme quantidade de codigos de barras
            $sheet->getRowDimension($linha)->setRowHeight(14);
            if ($linhasBarras > 2) {
                $height = ($linhasBarras - 1) * 12;
                $sheet->getRowDimension($linha2)->setRowHeight($height);
            } else {
                $sheet->getRowDimension($linha2)->setRowHeight(10);
            }

            // Saldo Deposito
            $sheet->mergeCells("D{$linha}:D{$linha2}");
            $sheet->setCellValue("D{$linha}", $prod->saldoquantidade);

            // Filiais
            foreach ($prod->destinos as $dest) {
                if (!@$coluna = $colunas[$dest->codestoquelocal]) {
                    continue;
                }

                // Saldo
                $sheet->setCellValue("{$coluna}{$linha}", $dest->transferir);

                // percentual
                if (!empty($dest->estoquemaximo)) {
                    $str = "=({$dest->saldoquantidade}+{$coluna}{$linha})/{$dest->estoquemaximo}";
                } else {
                    $str = 'nada';
                }
                $sheet->setCellValue("{$coluna}{$linha2}", $str);
            }

            // formata quantidades dos estoques das filiais
            $sheet->getStyle("E{$linha}:H{$linha}")->getFont()->setSize(12);
            $sheet->getStyle("E{$linha}:H{$linha}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$linha}:H{$linha}")->getFont()->setBold(true);

            // formata percentuais dos estoques das filiais
            $sheet->getStyle("E{$linha2}:H{$linha2}")->getFont()->setSize(8);
            $sheet->getStyle("E{$linha2}:H{$linha2}")->getNumberFormat()->setFormatCode('0%');
            $sheet->getStyle("E{$linha2}:H{$linha2}")->getFont()->getColor()->setARGB('FF999999');

            // Saldo Final Deposito
            $str = "=D{$linha}-E{$linha}-F{$linha}-G{$linha}";
            $sheet->setCellValue("H{$linha}", $str);
            $sheet->getStyle("H{$linha}:H{$linha}")->getNumberFormat()->setFormatCode('#,##0');

            // Percentual Deposito
            if (!empty($prod->estoquemaximo)) {
                $str = "=H{$linha}/{$prod->estoquemaximo}";
            } else {
                $str = 'nada';
            }
            $sheet->setCellValue("H{$linha2}", $str);
            $sheet->getStyle("H{$linha2}")->getNumberFormat()->setFormatCode('0%');

            $linha += 2;
        }
        $linhaFinal = $linha-1;

        // Filtro
        $spreadsheet->getActiveSheet()->setAutoFilter("A{$linhaCabecalho}:H{$linhaFinal}");

        // Desenha Grade ao redor da tabela
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF999999'],
                ],
            ],
        ];
        $sheet->getStyle("A{$linhaCabecalho}:H{$linhaFinal}")->applyFromArray($styleArray);

        // Cores das Colunas de Codigo e Barras
        $sheet->getStyle("A{$linhaInicial}:A{$linhaFinal}")->getFont()->getColor()->setARGB('FF999999');
        $sheet->getStyle("C{$linhaInicial}:C{$linhaFinal}")->getFont()->getColor()->setARGB('FF999999');

        // Alinhamento dos dados Centralizados e no topo da celula
        $sheet->getStyle("A{$linhaInicial}:A{$linhaFinal}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$linhaInicial}:H{$linhaFinal}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:H{$linhaFinal}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        // Fundo Azul no Botanico
        $sheet->getStyle("E{$linhaInicial}:E{$linhaFinal}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("E{$linhaInicial}:E{$linhaFinal}")->getFill()->getStartColor()->setARGB('FFDCE8F2');

        // Fundo Vermelho no Centro
        $sheet->getStyle("F{$linhaInicial}:F{$linhaFinal}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("F{$linhaInicial}:F{$linhaFinal}")->getFill()->getStartColor()->setARGB('FFF2DCDB');

        // Fundo Amarelo no Imperial
        $sheet->getStyle("G{$linhaInicial}:G{$linhaFinal}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("G{$linhaInicial}:G{$linhaFinal}")->getFill()->getStartColor()->setARGB('FFFAFB9C');

        // Quebra automatica para descricao do produto e barras
        $sheet->getStyle("B{$linhaInicial}:B{$linhaFinal}")->getAlignment()->setShrinkToFit(true);
        $sheet->getStyle("C{$linhaInicial}:C{$linhaFinal}")->getAlignment()->setWrapText(true);

        // opcoes planilha
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
        $sheet->getHeaderFooter()->setOddFooter('&L&B' . $spreadsheet->getProperties()->getTitle() . '&RPágina &P de &N');

        // Gera Arquivo XLSX
        $writer = new Xlsx($spreadsheet);
        $dir = "/media/publico/Documentos/Estoque/Transferencias/";
        $arquivo = $dir . Carbon::today()->format('Y-m-d') . " - Deposito - Filiais - {$marca->marca}.xlsx";
        $v = 0;
        while (file_exists($arquivo)) {
            $v++;
            $arquivo = $dir . Carbon::today()->format('Y-m-d') . " - Deposito - Filiais - {$marca->marca} - v{$v}.xlsx";
        }
        $writer->save($arquivo);
        chmod($arquivo, 0666);
    }
}
