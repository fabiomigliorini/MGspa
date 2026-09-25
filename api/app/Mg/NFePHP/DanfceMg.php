<?php

namespace Mg\NFePHP;

use NFePHP\DA\NFe\Danfce;

/**
 * DANFE da NFCe com o bloco de totais completo.
 *
 * O blocoIV da sped-da (NFePHP\DA\NFe\Traits\TraitBlocoIV) so conhece desconto e frete:
 * calcula o "Valor Total" como vNF + vDesc - vFrete e nunca imprime vSeg nem vOutro.
 * Com os juros do vale-compras entrando como outras despesas, o cupom mostrava os itens
 * somando 27,29 e um "Valor a Pagar" de 32,46, sem nenhuma linha explicando os 5,17 de
 * diferenca.
 *
 * Aqui o "Valor Total" sai do proprio vProd e cada acessorio que altera o valor da nota
 * (desconto, frete, seguro, outras despesas) ganha a sua linha quando for diferente de
 * zero. E a unica coisa que esta classe muda: todo o resto do cupom continua sendo o da
 * sped-da.
 */
class DanfceMg extends Danfce
{
    /**
     * Fator de conversao de ponto (tamanho da fonte) para milimetro (unidade do PDF).
     */
    const MM_POR_PONTO = 25.4 / 72;

    /**
     * Rotulo de cada acessorio e a tag correspondente dentro do ICMSTot.
     */
    const ACESSORIOS = [
        'Desconto R$' => 'vDesc',
        'Frete R$' => 'vFrete',
        'Seguro R$' => 'vSeg',
        'Outras Despesas R$' => 'vOutro',
    ];

    public function render($logo = '')
    {
        // A sped-da trata a altura do bloco de totais como constante (16mm, medida para as
        // 5 linhas dela) e mede o comprimento da bobina com esse valor, num metodo privado
        // que nao da pra sobrescrever. Como aqui o numero de linhas varia, a altura tem que
        // estar certa antes de montar o PDF -- e so aqui os setters de papel e fonte ja
        // foram todos chamados.
        $this->bloco4H = $this->alturaBlocoTotais();

        return parent::render($logo);
    }

    protected function blocoIV($y)
    {
        $altura = 0;
        foreach ($this->linhasTotais() as $linha) {
            list($rotulo, $valor, $aFont) = $linha;

            $this->pdf->textBox(
                $this->margem,
                $y + $altura,
                $this->wPrint / 2,
                3,
                $rotulo,
                $aFont,
                'T',
                'L',
                false,
                '',
                false
            );
            $this->pdf->textBox(
                $this->margem + $this->wPrint / 2,
                $y + $altura,
                $this->wPrint / 2,
                3,
                $valor,
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );

            $altura += $aFont['size'] * self::MM_POR_PONTO;
        }

        $this->pdf->dashedHLine($this->margem, $this->bloco4H + $y, $this->wPrint, 0.1, 30);
        return $this->bloco4H + $y;
    }

    /**
     * As linhas do bloco de totais, cada uma como [rotulo, valor, fonte].
     *
     * Serve tanto para desenhar quanto para medir o bloco, entao as duas coisas nunca
     * discordam sobre quantas linhas existem.
     */
    private function linhasTotais()
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];

        $linhas = [
            ['Qtde total de itens', $this->det->length, $aFont],
            ['Valor Total R$', $this->formataValor($this->getTagValue($this->ICMSTot, 'vProd')), $aFont],
        ];

        foreach (self::ACESSORIOS as $rotulo => $tag) {
            $valor = (float) $this->getTagValue($this->ICMSTot, $tag);
            if (empty($valor)) {
                continue;
            }
            $linhas[] = [$rotulo, $this->formataValor($valor), $aFont];
        }

        // O total a pagar em negrito e maior, como na sped-da
        $aFontTotal = [
            'font' => $this->fontePadrao,
            'size' => $this->paperwidth < 70 ? 8 : 10,
            'style' => 'B',
        ];
        $linhas[] = ['Valor a Pagar R$', $this->formataValor($this->getTagValue($this->ICMSTot, 'vNF')), $aFontTotal];

        return $linhas;
    }

    private function alturaBlocoTotais()
    {
        $altura = 0;
        foreach ($this->linhasTotais() as $linha) {
            $altura += $linha[2]['size'] * self::MM_POR_PONTO;
        }
        // folga antes da linha tracejada, igual a da sped-da
        return $altura + 1;
    }

    private function formataValor($valor)
    {
        return number_format((float) $valor, 2, ',', '.');
    }
}
