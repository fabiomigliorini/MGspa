<?php

namespace Mg\Rh;

use Dompdf\Dompdf;

class AcertoReciboPdf
{
    public static function gerar(int $codperiodo, array $colaboradores = [], ?int $codunidadenegocio = null): string
    {
        ini_set('memory_limit', '256M');

        $periodo = Periodo::findOrFail($codperiodo);

        $eventos = PeriodoColaboradorAcerto::whereNull('inativo')
            ->whereHas('PeriodoColaborador', fn ($q) => $q->where('codperiodo', $codperiodo))
            ->with([
                'PeriodoColaborador.Colaborador.Pessoa',
                'PeriodoColaborador.Colaborador.Filial.Pessoa',
                'PeriodoColaborador.Setor',
                'MovimentoTituloS.Titulo',
            ])
            ->orderBy('data')
            ->orderBy('codperiodocolaboradoracerto')
            ->get();

        if (!empty($colaboradores)) {
            $eventos = $eventos->whereIn('codperiodocolaborador', $colaboradores);
        }
        if ($codunidadenegocio) {
            $eventos = $eventos->filter(
                fn ($e) => optional(optional($e->PeriodoColaborador)->Setor)->codunidadenegocio == $codunidadenegocio
            );
        }

        $grupos = $eventos->groupBy('codperiodocolaborador');
        if ($grupos->isEmpty()) {
            return '';
        }

        $periodoLabel = $periodo->periodoinicial->format('d/m/Y') . ' a ' . $periodo->periodofinal->format('d/m/Y');

        $html = view('rh.acerto-recibos', compact('grupos', 'periodoLabel'))->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
