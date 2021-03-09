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
}
