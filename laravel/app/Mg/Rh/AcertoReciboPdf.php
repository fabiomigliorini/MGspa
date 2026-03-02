<?php

namespace Mg\Rh;

use Dompdf\Dompdf;
use Mg\Titulo\LiquidacaoTitulo;

class AcertoReciboPdf
{
    public static function gerar(int $codperiodo, array $colaboradores = []): string
    {
        $query = LiquidacaoTitulo::where('codperiodo', $codperiodo)
            ->whereNull('estornado')
            ->with([
                'MovimentoTituloS.Titulo.Filial.Pessoa.Cidade.Estado',
                'MovimentoTituloS.Titulo.PeriodoColaboradorS.ColaboradorRubricaS',
                'Pessoa.Cidade.Estado',
                'UsuarioCriacao',
            ]);

        if (!empty($colaboradores)) {
            $codpessoas = PeriodoColaborador::whereIn('codperiodocolaborador', $colaboradores)
                ->with('Colaborador')
                ->get()
                ->pluck('Colaborador.codpessoa')
                ->filter()
                ->toArray();
            $query->whereIn('codpessoa', $codpessoas);
        }

        $liquidacoes = $query->get();

        $html = '';
        foreach ($liquidacoes as $liq) {
            if ($liq->credito > 0) {
                $html .= view('liquidacao-titulo.recibo-recebimento', compact('liq'))->render();
            }
            if ($liq->debito > 0) {
                $html .= view('liquidacao-titulo.recibo-pagamento', compact('liq'))->render();
            }
        }

        if (empty($html)) {
            return '';
        }

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

}
