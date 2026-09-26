<?php

namespace Mg\Vale;

use App\Http\Requests\Mg\Vale\ValeEmitidoRequest;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ValeEmitidoController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    // Scroll infinito: 50 por pagina; acabou quando vier menos que isso.
    public function index(ValeEmitidoRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $page = (int) $request->input('page', 1);
        $vales = ValeEmitidoService::pesquisar(
            $request->validated(),
            ValeEmitidoService::POR_PAGINA,
            ($page - 1) * ValeEmitidoService::POR_PAGINA
        );

        return ValeEmitidoResource::collection($vales);
    }

    /**
     * Impressao da lista filtrada. ?html=1 devolve o HTML cru, para ajustar o
     * layout sem re-renderizar o PDF.
     */
    public function relatorio(ValeEmitidoRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $filtros = $request->validated();
        $vales = ValeEmitidoService::pesquisar($filtros, ValeEmitidoService::LIMITE_RELATORIO + 1);
        if (count($vales) > ValeEmitidoService::LIMITE_RELATORIO) {
            abort(422, 'O relatório está limitado a ' . ValeEmitidoService::LIMITE_RELATORIO . ' vales. Ajuste os filtros.');
        }

        if ($request->boolean('html')) {
            return response(ValeEmitidoRelatorioService::html($vales, $filtros), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return response(ValeEmitidoRelatorioService::pdf($vales, $filtros), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="vales-emitidos.pdf"',
        ]);
    }
}
