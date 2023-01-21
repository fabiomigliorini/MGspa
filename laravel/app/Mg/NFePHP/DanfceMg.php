<?php

namespace Mg\NFePHP;

use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\Legacy\Pdf;

class DanfceMg extends Danfce
{
    protected $blocoInformacoesAdicionais = 0;

    protected function monta(
        $logo = ''
    ) {
        $this->paperwidth = 75;
        if (!empty($logo)) {
            $this->logomarca = $this->adjustImage($logo, true);
        }
        $this->orientacao = 'P';
        $this->papel = [$this->paperwidth, 100];
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);

        $tamPapelVert = $this->calculatePaperLength();
        $this->papel = [$this->paperwidth, $tamPapelVert];
        $this->logoAlign = 'L';
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);

        //margens do PDF, em milímetros. Obs.: a margem direita é sempre igual à
        //margem esquerda. A margem inferior *não* existe na FPDF, é definida aqui
        //apenas para controle se necessário ser maior do que a margem superior
        $margSup = $this->margem;
        $margEsq = $this->margem;
        $margInf = $this->margem;
        // posição inicial do conteúdo, a partir do canto superior esquerdo da página
        $xInic = $margEsq;
        $yInic = $margSup;
        $maxW = $this->paperwidth;
        $maxH = $tamPapelVert;
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm: largura da folha menos as margens esq/direita
        $this->wPrint = $maxW-($margEsq*2);
        //comprimento (altura) imprimivel em mm: altura da folha menos as margens
        //superior e inferior
        $this->hPrint = $maxH-$margSup-$margInf;
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        $this->pdf->setMargins($margEsq, $margSup); // fixa as margens
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->open(); // inicia o documento
        $this->pdf->addPage($this->orientacao, $this->papel); // adiciona a primeira página
        $this->pdf->setLineWidth(0.1); // define a largura da linha
        $this->pdf->setTextColor(0, 0, 0);

        $y = $this->blocoI(); //cabecalho
        $y = $this->blocoII($y); //informação cabeçalho fiscal e contingência

        $y = $this->blocoIII($y); //informação dos itens
        $y = $this->blocoIV($y); //informação sobre os totais
        $y = $this->blocoV($y); //informação sobre pagamento

        $y = $this->blocoVI($y); //informações sobre consulta pela chave


        $y = $this->blocoVII($y); //informações sobre o consumidor e dados da NFCe
        $y = $this->blocoVIII($y); //QRCODE
        $y = $this->blocoInformacoesAdicionais($y); //informações sobre consulta pela chave
        $y = $this->blocoIX($y); //informações sobre tributos
        $y = $this->blocoX($y); //creditos

        $ymark = $maxH/4;
        if ($this->tpAmb == 2) {
            $this->pdf->setTextColor(120, 120, 120);
            $texto = "SEM VALOR FISCAL\nEmitida em ambiente de homologacao";
            $aFont = ['font' => $this->fontePadrao, 'size' => 14, 'style' => 'B'];
            $ymark += $this->pdf->textBox(
                $this->margem,
                $ymark,
                $this->wPrint,
                $maxH/2,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );
            $this->pdf->setTextColor(0, 0, 0);
        }
        if ($this->canceled) {
            $this->pdf->setTextColor(120, 120, 120);
            $texto = "CANCELADA";
            $aFont = ['font' => $this->fontePadrao, 'size' => 24, 'style' => 'B'];
            $this->pdf->textBox(
                $this->margem,
                $ymark+4,
                $this->wPrint,
                $maxH/2,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $this->pdf->textBox(
                $this->margem,
                $ymark+14,
                $this->wPrint,
                $maxH/2,
                $this->submessage,
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );
            $this->pdf->setTextColor(0, 0, 0);
        }

        if (!$this->canceled && $this->tpEmis == 9 && $this->offline_double) {
            $this->setViaEstabelecimento();
            //não está cancelada e foi emitida OFFLINE e está ativada a dupla impressão
            $this->pdf->addPage($this->orientacao, $this->papel); // adiciona a primeira página
            $this->pdf->setLineWidth(0.1); // define a largura da linha
            $this->pdf->setTextColor(0, 0, 0);
            $y = $this->blocoI(); //cabecalho
            $y = $this->blocoII($y); //informação cabeçalho fiscal e contingência
            $y = $this->blocoIII($y); //informação dos itens
            $y = $this->blocoIV($y); //informação sobre os totais
            $y = $this->blocoV($y); //informação sobre pagamento
            $y = $this->blocoVI($y); //informações sobre consulta pela chave
            $y = $this->blocoVII($y); //informações sobre o consumidor e dados da NFCe
            $y = $this->blocoVIII($y); //QRCODE
            $y = $this->blocoIX($y); //informações sobre tributos
            $y = $this->blocoX($y); //creditos
            $ymark = $maxH/4;
            if ($this->tpAmb == 2) {
                $this->pdf->setTextColor(120, 120, 120);
                $texto = "SEM VALOR FISCAL\nEmitida em ambiente de homologacao";
                $aFont = ['font' => $this->fontePadrao, 'size' => 14, 'style' => 'B'];
                $ymark += $this->pdf->textBox(
                    $this->margem,
                    $ymark,
                    $this->wPrint,
                    $maxH/2,
                    $texto,
                    $aFont,
                    'T',
                    'C',
                    false,
                    '',
                    false
                );
            }
            $this->pdf->setTextColor(0, 0, 0);
        }
    }

    private function calculatePaperLength()
    {
        $wprint = $this->paperwidth - (2 * $this->margem);
        $this->bloco3H = $this->calculateHeightItens($wprint * $this->descPercent);
        $this->bloco5H = $this->calculateHeightPag();
        $this->blocoInformacoesAdicionais = $this->calculateHeightInformacoesAdicionais();

        $length = $this->bloco1H //cabecalho
            + $this->bloco2H //informação fiscal
            + $this->bloco3H //itens
            + $this->bloco4H //totais
            + $this->bloco5H //formas de pagamento
            + $this->bloco6H //informação para consulta
            + $this->bloco7H //informações do consumidor
            + $this->bloco8H //qrcode
            + $this->blocoInformacoesAdicionais //informação para consulta
            + $this->bloco9H //informações sobre tributos
            + $this->bloco10H; //informações do integrador
        return $length;
    }

    private function calculateHeightInformacoesAdicionais()
    {
        // Busca Texto Dados Adicionais
        $this->textoAdic = "";
        $infCpl = $this->infAdic->getElementsByTagName("infCpl");
        if ($infCpl->length == 0) {
            return 0;
        }
        $this->textoAdic = $infCpl->item(0)->nodeValue;
        $this->textoAdic = str_replace(";", "\n", $this->textoAdic);

        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->wPrint = $this->wPrint??76;
        $numlinhasdados         = $this->pdf->getNumLines($this->textoAdic, $this->wPrint, $aFont);
        $hdadosadic             = ceil($numlinhasdados * (2.469444444));

        return $hdadosadic;
    }

    protected function blocoInformacoesAdicionais($y)
    {
        if (empty($this->textoAdic)) {
            return $y;
        }
        $this->pdf->dashedHLine($this->margem, $y, $this->wPrint, 0.1, 30);
        $y += 0.5;

        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $y1 = $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            2,
            $this->textoAdic,
            $aFont,
            'T',
            'C',
            false,
            '',
            false
        );
        $y += $y1 + 1;

        $this->pdf->dashedHLine($this->margem, $y, $this->wPrint, 0.1, 30);
        $y += 1;

        return $y;
    }

    protected function blocoIV($y)
    {
        //$this->bloco4H = 13;

        //$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco4H, '', $aFont, 'T', 'C', true, '', false);

        $qtd = $this->det->length;
        $valor = $this->getTagValue($this->ICMSTot, 'vNF');
        $desconto = $this->getTagValue($this->ICMSTot, 'vDesc');
        $frete = $this->getTagValue($this->ICMSTot, 'vFrete');
        $outro = $this->getTagValue($this->ICMSTot, 'vOutro');
        $bruto = $this->getTagValue($this->ICMSTot, 'vProd');

        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $texto = "Qtde total de itens";
        $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint / 2,
            3,
            $texto,
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );
        $y1 = $this->pdf->textBox(
            $this->margem + $this->wPrint / 2,
            $y,
            $this->wPrint / 2,
            3,
            $qtd,
            $aFont,
            'T',
            'R',
            false,
            '',
            false
        );

        $texto = "Valor Total R$";
        $this->pdf->textBox(
            $this->margem,
            $y + $y1,
            $this->wPrint / 2,
            3,
            $texto,
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );
        $texto = number_format((float) $bruto, 2, ',', '.');
        $y2 = $this->pdf->textBox(
            $this->margem + $this->wPrint / 2,
            $y + $y1,
            $this->wPrint / 2,
            3,
            $texto,
            $aFont,
            'T',
            'R',
            false,
            '',
            false
        );

        $y3 = 0;
        if ($desconto > 0) {
            $texto = "Desconto R$";
            $this->pdf->textBox(
                $this->margem,
                $y + $y1 + $y2,
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'L',
                false,
                '',
                false
            );
            $texto = number_format((float) $desconto, 2, ',', '.');
            $y3 = $this->pdf->textBox(
                $this->margem + $this->wPrint / 2,
                $y + $y1 + $y2,
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );
        }

        $y4 = 0;
        if ($frete > 0) {
            $texto = "Frete R$";
            $this->pdf->textBox(
                $this->margem,
                $y + $y1 + $y2 + $y3,
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'L',
                false,
                '',
                false
            );
            $texto = number_format((float) $frete, 2, ',', '.');
            $y4 = $this->pdf->textBox(
                $this->margem + $this->wPrint / 2,
                $y + $y1 + $y2 + $y3,
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );
        }

        $y5 = 0;
        if ($outro > 0) {
            $texto = "Outros R$";
            $this->pdf->textBox(
                $this->margem,
                $y + $y1 + $y2 + $y3 + $y4,
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'L',
                false,
                '',
                false
            );
            $texto = number_format((float) $outro, 2, ',', '.');
            $y5 = $this->pdf->textBox(
                $this->margem + $this->wPrint / 2,
                $y + $y1 + $y2 + $y3 + $y4,
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );
        }

        $fsize = 10;
        if ($this->paperwidth < 70) {
            $fsize = 8;
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => $fsize, 'style' => 'B'];
        $texto = "Valor a Pagar R$";
        $this->pdf->textBox(
            $this->margem,
            $y + $y1 + $y2 + $y3 + $y4 + $y5,
            $this->wPrint / 2,
            3,
            $texto,
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );
        $texto = number_format((float) $valor, 2, ',', '.');
        $y6 = $this->pdf->textBox(
            $this->margem + $this->wPrint / 2,
            $y + $y1 + $y2 + $y3 + $y4 + $y5,
            $this->wPrint / 2,
            3,
            $texto,
            $aFont,
            'T',
            'R',
            false,
            '',
            false
        );

        $this->bloco4H = $y1 + $y2 + $y3 + $y4 + $y5 + $y6 + 1;

        $this->pdf->dashedHLine($this->margem, $this->bloco4H + $y, $this->wPrint, 0.1, 30);
        return $this->bloco4H + $y;
    }

    protected function blocoVII($y)
    {
        $nome = $this->getTagValue($this->dest, "xNome");
        $cnpj = $this->getTagValue($this->dest, "CNPJ");
        $cpf = $this->getTagValue($this->dest, "CPF");
        $rua = $this->getTagValue($this->enderDest, "xLgr");
        $numero = $this->getTagValue($this->enderDest, "nro");
        $complemento = $this->getTagValue($this->enderDest, "xCpl");
        $bairro = $this->getTagValue($this->enderDest, "xBairro");
        $mun = $this->getTagValue($this->enderDest, "xMun");
        $uf = $this->getTagValue($this->enderDest, "UF");
        $texto = '';
        $yPlus = 0;
        $y1 = 0;
        $y2 = 0;
        $y3 = 0;
        $y4 = 0;
        $y5 = 0;
        if (!empty($cnpj)) {
            $texto = "CONSUMIDOR - CNPJ "
                . $this->formatField($cnpj, "##.###.###/####-##") . " - " . $nome;
        } elseif (!empty($cpf)) {
            $texto = "CONSUMIDOR - CPF "
                . $this->formatField($cpf, "###.###.###-##") . " = " . $nome;
        } else {
            $texto = 'CONSUMIDOR NÃO IDENTIFICADO';
            $yPlus = 1;
        }
        if (!empty($rua)) {
            $texto .= "\n {$rua}, {$numero} {$complemento} {$bairro} {$mun}-{$uf}";
        }
        if ($this->getTagValue($this->nfeProc, "xMsg")) {
            $texto .= "\n {$this->getTagValue($this->nfeProc, "xMsg")}";
            $this->bloco7H += 4;
        }
        $subSize = 0;
        if ($this->paperwidth < 70) {
            $subSize = 1.5;
        }
        if ($this->tpEmis == 9) {
            $aFont = ['font'=> $this->fontePadrao, 'size' => (7-$subSize), 'style' => ''];
            $y += 2*$yPlus;
            $y1 = $this->pdf->textBox(
                $this->margem,
                $y,
                $this->wPrint,
                $this->bloco7H,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );

            $y1 += 2*$yPlus;
            $num = str_pad($this->getTagValue($this->ide, "nNF"), 9, '0', STR_PAD_LEFT);
            $serie = str_pad($this->getTagValue($this->ide, "serie"), 3, '0', STR_PAD_LEFT);
            $data = (new \DateTime($this->getTagValue($this->ide, "dhEmi")))->format('d/m/Y H:i:s');
            $texto = "NFCe n. {$num} Série {$serie} {$data}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 8, 'style' => 'B'];
            $y2 = $this->pdf->textBox(
                $this->margem,
                $y+$y1,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );

            $texto = $this->via;
            $y3 = $this->pdf->textBox(
                $this->margem,
                $y+$y1+$y2,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );

            //contingencia offline
            $texto = "EMITIDA EM CONTINGÊNCIA";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $y4 = $this->pdf->textBox(
                $this->margem,
                $y+$y1+$y2+$y3,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'B',
                'C',
                false,
                '',
                true
            );

            $texto = "Pendente de autorização";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 8, 'style' => 'I'];
            $y5 = $this->pdf->textBox(
                $this->margem,
                $y+$y1+$y2+$y3+$y4,
                $this->wPrint,
                3,
                $texto,
                $aFont,
                'B',
                'C',
                false,
                '',
                true
            );
        } elseif ($this->tpEmis == 4) {
            $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
            $y1 = $this->pdf->textBox(
                $this->margem,
                $y+1,
                $this->wPrint,
                $this->bloco7H,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );

            $num = str_pad($this->getTagValue($this->ide, "nNF"), 9, '0', STR_PAD_LEFT);
            $serie = str_pad($this->getTagValue($this->ide, "serie"), 3, '0', STR_PAD_LEFT);
            $data = (new \DateTime($this->getTagValue($this->ide, "dhEmi")))->format('d/m/Y H:i:s');
            $texto = "NFCe n. {$num} Série {$serie} {$data}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (8-$subSize), 'style' => ''];
            $y2 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );
            $texto = "DANFE-NFC-e Impresso em contingência - EPEC";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (10-$subSize), 'style' => 'B'];
            $y2 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1+3,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );

            $texto = "Regularmente recebido pela administração tributária autorizadora";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (8-$subSize), 'style' => ''];
            $y2 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1+$y2+3,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );
            if (!empty($this->dom->getElementsByTagName('dhCont'))) {
                $dhCont = $this->dom->getElementsByTagName('dhCont')->item(0)->nodeValue;
                $dt = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $dhCont);
                $texto = "Data de entrada em contingência : " . $dt->format('d/m/Y H:i:s');
                $aFont = ['font'=> $this->fontePadrao, 'size' => (7-$subSize), 'style' => ''];
                $y2 = $this->pdf->textBox(
                    $this->margem,
                    $y+1+$y1+$y2+6,
                    $this->wPrint,
                    4,
                    $texto,
                    $aFont,
                    'B',
                    'C',
                    false,
                    '',
                    true
                );
            }
        } else {
            $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
            $y1 = $this->pdf->textBox(
                $this->margem,
                $y+1,
                $this->wPrint,
                $this->bloco7H,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );

            $num = str_pad($this->getTagValue($this->ide, "nNF"), 9, '0', STR_PAD_LEFT);
            $serie = str_pad($this->getTagValue($this->ide, "serie"), 3, '0', STR_PAD_LEFT);
            $data = (new \DateTime($this->getTagValue($this->ide, "dhEmi")))->format('d/m/Y H:i:s');
            $texto = "NFCe n. {$num} Série {$serie} {$data}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (8-$subSize), 'style' => 'B'];
            $y2 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );

            $protocolo = '';
            $data = '';
            if (!empty($this->nfeProc)) {
                $protocolo = $this->formatField($this->getTagValue($this->nfeProc, 'nProt'), '### ########## ##');
                $data = (new \DateTime($this->getTagValue($this->nfeProc, "dhRecbto")))->format('d/m/Y H:i:s');
            }

            $texto = "Protocolo de Autorização:  {$protocolo}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (8-$subSize), 'style' => ''];
            $y3 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1+$y2,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );

            $texto = "Data de Autorização:  {$data}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (8-$subSize), 'style' => ''];
            $y4 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1+$y2+$y3,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );
        }

        $this->bloco7H = $y1 + $y2 + $y3 + $y4 + $y5 + 3;

        $this->pdf->dashedHLine($this->margem, $this->bloco7H+$y, $this->wPrint, 0.1, 30);
        return $this->bloco7H + $y;
    }
}
