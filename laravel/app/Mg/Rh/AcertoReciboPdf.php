<?php

namespace Mg\Rh;

use Dompdf\Dompdf;
use Mg\Titulo\LiquidacaoTitulo;

class AcertoReciboPdf
{
    public static function gerar(int $codperiodo, array $colaboradores = []): string
    {
        //ini_set('memory_limit', '256M');

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

        if ($liquidacoes->isEmpty()) {
            return '';
        }

        $html = view('rh.acerto-recibos', compact('liquidacoes'))->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
