<?php

namespace Mg\Dimp;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

/**
 * Conciliação DIMP do mês.
 *
 * Aceita ?html=1 para ajustar o layout sem re-renderizar o PDF a cada
 * tentativa -- mesmo contrato do relatório de romaneios e do modelo de vale.
 */
class DimpConciliacaoController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    public function relatorio(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $filtros = [
            'ano' => $request->input('ano'),
            'mes' => $request->input('mes'),
            'codfilial' => $request->input('codfilial') ?: null,
        ];

        if ($request->boolean('html')) {
            return response(DimpConciliacaoService::html($filtros), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return response(DimpConciliacaoService::pdf($filtros), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="conciliacao-dimp-'
                . $filtros['ano'] . '-' . str_pad($filtros['mes'], 2, '0', STR_PAD_LEFT) . '.pdf"',
        ]);
    }
}
