<?php

namespace Mg\Ibpt;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Usuario\Autorizador;
use Mg\Ibpt\Requests\IbptImportarRequest;
use Mg\Ibpt\Resources\IbptStatusResource;

/**
 * Tabela do IBPT usada no cálculo dos tributos aproximados (Lei 12.741).
 *
 * A carga é feita pela tela do app de notas, com os CSVs baixados no site do IBPT -
 * um request por UF, porque o dedup do axios do front colide quando dois POSTs
 * multipart vão para a mesma URL.
 */
class IbptController extends MgController
{
    private const GRUPOS = ['Administrador', 'Contador'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        return IbptStatusResource::collection(collect(IbptService::status()));
    }

    public function importar(IbptImportarRequest $request, $uf)
    {
        Autorizador::autoriza(self::GRUPOS);

        return response()->json(
            IbptService::importar($request->file('arquivo'), $uf),
            200
        );
    }
}
