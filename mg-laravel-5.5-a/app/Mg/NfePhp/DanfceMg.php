<?php

namespace Mg\NfePhp;

use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\Legacy\Pdf;

class DanfceMg extends Danfce
{
    public function __construct(
        $docXML = '',
        $sPathLogo = '',
        $mododebug = 0,
        // habilita os erros do sistema
        $idToken = '',
        $emitToken = '',
        $urlQR = ''
    ) {
        parent::__construct($docXML, $sPathLogo, $mododebug, $idToken, $emitToken, $urlQR);
        $this->fontePadrao = 'helvetica';
        // $this->hLinha = 50;
        $this->hMaxLinha = 12;
    }

    public function montaDANFE(
        $orientacao = 'P',
        $papel = '',
        $logoAlign = 'C',
        $classPdf = false,
        $depecNumReg = ''
    ) {
        $qtdItens = $this->det->length;
        $qtdPgto = $this->pag->length;
        $hMaxLinha = $this->hMaxLinha;
        $hBoxLinha = $this->hBoxLinha;
        $hLinha = $this->hLinha;
        $tamPapelVert = 160 + 16 + (($qtdItens - 1) * $hMaxLinha) + ($qtdPgto * $hLinha);
        // verifica se existe informações adicionais
        $this->textoAdic = '';
        if (isset($this->infAdic)) {
            $this->textoAdic .= !empty($this->infAdic->getElementsByTagName('infCpl')->item(0)->nodeValue) ?
            'Inf. Contribuinte: '.
            trim($this->pAnfavea($this->infAdic->getElementsByTagName('infCpl')->item(0)->nodeValue)) : '';
            if (!empty($this->textoAdic)) {
                $this->textoAdic = str_replace(";", "\n", $this->textoAdic);
                $alinhas = explode("\n", $this->textoAdic);
                $numlinhasdados = 0;
                $tempPDF = new Pdf(); // cria uma instancia temporaria da class pdf
                $tempPDF->SetFont('Times', '', '8'); // seta a font do PDF
                foreach ($alinhas as $linha) {
                    $linha = trim($linha);
                    $numlinhasdados += $tempPDF->WordWrap($linha, 76 - 0.2);
                }
                $hdadosadic = round(($numlinhasdados + 1) * $tempPDF->fontSize, 0);
                if ($hdadosadic < 5) {
                    $hdadosadic = 5;
                }
                // seta o tamanho do papel
                $tamPapelVert += $hdadosadic;
            }
        }
        //se a orientação estiver em branco utilizar o padrão estabelecido na NF
        if ($orientacao == '') {
            $orientacao = 'P';
        }
        $this->orientacao = $orientacao;
        $this->papel = array(80,$tamPapelVert);
        $this->logoAlign = $logoAlign;
        //$this->situacao_externa = $situacaoExterna;
        $this->numero_registro_dpec = $depecNumReg;
        //instancia a classe pdf
        if ($classPdf) {
            $this->pdf = $classPdf;
        } else {
            $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        }
        //margens do PDF, em milímetros. Obs.: a margem direita é sempre igual à
        //margem esquerda. A margem inferior *não* existe na FPDF, é definida aqui
        //apenas para controle se necessário ser maior do que a margem superior
        $margSup = 2;
        $margEsq = 2;
        $margInf = 2;
        // posição inicial do conteúdo, a partir do canto superior esquerdo da página
        $xInic = $margEsq;
        $yInic = $margSup;
        $maxW = 80;
        $maxH = $tamPapelVert;
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm: largura da folha menos as margens esq/direita
        $this->wPrint = $maxW-($margEsq*2)-2;
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
        $this->pdf->setLineWidth(1); // define a largura da linha
        $this->pdf->setTextColor(0, 0, 0);
        //$this->pTextBox(0, 0, $maxW, $maxH); // POR QUE PRECISO DESA LINHA?
        $hcabecalho = 27;//para cabeçalho (dados emitente mais logomarca)  (FIXO)
        $hcabecalhoSecundario = 10;//para cabeçalho secundário (cabeçalho sefaz) (FIXO)
        $hprodutos = $hLinha + ($qtdItens*$hMaxLinha) ;//box poduto
        $hTotal = 12; //box total (FIXO)
        $hpagamentos = $hLinha + ($qtdPgto*$hLinha);//para pagamentos
        if (!empty($this->vTroco)) {
            $hpagamentos += $hLinha;
        }
        $hmsgfiscal = 21;// para imposto (FIXO)
        if (!isset($this->dest)) {
            $hcliente = 6;// para cliente (FIXO)
        } else {
            $hcliente = 12;
        }// para cliente (FIXO)};
        $hQRCode = 50;// para qrcode (FIXO)
        $hCabecItens = 4;//cabeçalho dos itens

        $hUsado = $hCabecItens;
        $w2 = round($this->wPrint*0.31, 0);
        $totPag = 1;
        $pag = 1;
        $x = $xInic;
        //COLOCA CABEÇALHO
        $y = $yInic;
        $y = $this->pCabecalhoDANFE($x, $y, $hcabecalho, $pag, $totPag);
        //COLOCA CABEÇALHO SECUNDÁRIO
        $y = $hcabecalho;
        $y = $this->pCabecalhoSecundarioDANFE($x, $y, $hcabecalhoSecundario);
        //COLOCA PRODUTOS
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario;
        $y = $this->pProdutosDANFE($x, $y, $hprodutos);
        //COLOCA TOTAL
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos;
        $y = $this->pTotalDANFE($x, $y, $hTotal);
        //COLOCA PAGAMENTOS
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos + $hTotal;
        $y = $this->pPagamentosDANFE($x, $y, $hpagamentos);
        //COLOCA MENSAGEM FISCAL
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos + $hTotal+ $hpagamentos;
        $y = $this->pFiscalDANFE($x, $y, $hmsgfiscal);
        //COLOCA CONSUMIDOR
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos + $hTotal + $hpagamentos + $hmsgfiscal;
        $y = $this->pConsumidorDANFE($x, $y, $hcliente);
        //COLOCA QRCODE
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos
            + $hTotal + $hpagamentos + $hmsgfiscal + $hcliente;
        $y = $this->pQRDANFE($x, $y, $hQRCode);

        //adiciona as informações opcionais
        if (!empty($this->textoAdic)) {
            $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos
            + $hTotal + $hpagamentos + $hmsgfiscal + $hcliente + $hQRCode;
            $hInfAdic = 0;
            $y = $this->pInfAdic($x, $y, $hInfAdic);
        }

        //retorna o ID na NFe
        if ($classPdf!==false) {
            $aR = [
             'id'=>str_replace('NFe', '', $this->infNFe->getAttribute("Id")),
             'classe_PDF'=>$this->pdf
            ];
            return $aR;
        } else {
            return str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        }
    }
}
