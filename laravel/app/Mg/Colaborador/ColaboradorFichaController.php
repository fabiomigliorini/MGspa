<?php

namespace Mg\Colaborador;

use Mg\MgController;
use Dompdf\Dompdf;

class ColaboradorFichaController extends MgController
{
    public function ficha($codcolaborador)
    {
        $colaborador = Colaborador::with([
            'Pessoa.EstadoCivil',
            'Pessoa.GrauInstrucao',
            'Pessoa.Etnia',
            'Pessoa.CidadeNascimento.Estado',
            'Pessoa.Cidade.Estado',
            'Pessoa.EstadoCtps',
            'ColaboradorCargoS' => function ($query) {
                $query->whereNull('fim')
                    ->orderBy('inicio', 'desc');
            },
            'ColaboradorCargoS.Cargo'
        ])->findOrFail($codcolaborador);

        $html = view('colaborador.ficha-colaborador', [
            'colaborador' => $colaborador,
            'pessoa' => $colaborador->Pessoa
        ])->render();

        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->setDefaultFont('helvetica');
        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $nomeArquivo = 'Ficha_Colaborador_' . str_replace(['/', '\\', ' '], '_', $colaborador->Pessoa->pessoa) . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $nomeArquivo . '"');
    }
}
