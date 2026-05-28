<?php

namespace Mg\NotaFiscal;

use Dompdf\Dompdf;

class NotaFiscalEspelhoPdfService
{
    public static function pdf(NotaFiscal $notaFiscal)
    {
        // Carrega relacionamentos necessários
        $notaFiscal->load([
            'Filial.Pessoa.Cidade.Estado',
            'EstoqueLocal',
            'Pessoa.Cidade.Estado',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto.Ncm',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto.Cest',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ]);

        $filial = $notaFiscal->Filial;

        // Calcula totais de tributos
        $totais = static::calcularTotais($notaFiscal);

        $dompdf = new Dompdf();
        $html = view('notafiscal.espelho', compact('notaFiscal', 'filial', 'totais'))->render();
        $dompdf->loadHtml($html);

        // Papel A4
        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        return $dompdf->output();
    }

    private static function calcularTotais(NotaFiscal $notaFiscal): array
    {
        $totais = [
            'icmsbase' => 0,
            'icmsvalor' => 0,
            'icmsstbase' => 0,
            'icmsstvalor' => 0,
            'ipibase' => 0,
            'ipivalor' => 0,
            'pisvalor' => 0,
            'cofinsvalor' => 0,
            'ibpt' => 0,
        ];

        foreach ($notaFiscal->NotaFiscalProdutoBarraS as $item) {
            $totais['icmsbase'] += $item->icmsbase ?? 0;
            $totais['icmsvalor'] += $item->icmsvalor ?? 0;
            $totais['icmsstbase'] += $item->icmsstbase ?? 0;
            $totais['icmsstvalor'] += $item->icmsstvalor ?? 0;
            $totais['ipibase'] += $item->ipibase ?? 0;
            $totais['ipivalor'] += $item->ipivalor ?? 0;
            $totais['pisvalor'] += $item->pisvalor ?? 0;
            $totais['cofinsvalor'] += $item->cofinsvalor ?? 0;
        }

        return $totais;
    }
}
