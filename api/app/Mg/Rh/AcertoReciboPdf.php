<?php

namespace Mg\Rh;

use Dompdf\Dompdf;
use Mg\Titulo\LiquidacaoTitulo;

class AcertoReciboPdf
{
    public static function gerar(int $codperiodo, array $colaboradores = [], ?int $codunidadenegocio = null): string
    {
        ini_set('memory_limit', '256M');

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

        // Recibos de uma filial (unidade de negócio): filtra pelos colaboradores
        // cujo setor pertence à unidade.
        if ($codunidadenegocio) {
            $codpessoas = PeriodoColaborador::where('codperiodo', $codperiodo)
                ->whereHas('Setor', fn ($q) => $q->where('codunidadenegocio', $codunidadenegocio))
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
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
