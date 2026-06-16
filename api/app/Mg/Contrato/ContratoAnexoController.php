<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoAnexoStoreRequest;
use Illuminate\Http\Request;
use Mg\MgController;

/**
 * Anexos (PDFs) do contrato: contrato/{codcontrato}/anexo.
 * Arquivos em storage (não são model Eloquent), então retornam metadados crus.
 */
class ContratoAnexoController extends MgController
{
    public function index(Request $request, $codcontrato)
    {
        return response()->json(ContratoAnexoService::listagem((int) $codcontrato), 200);
    }

    public function store(ContratoAnexoStoreRequest $request, $codcontrato)
    {
        $ret = ContratoAnexoService::upload(
            (int) $codcontrato,
            $request->file('arquivo'),
            $request->label
        );
        return response()->json($ret, 201);
    }

    public function download($codcontrato, $nome)
    {
        return ContratoAnexoService::download((int) $codcontrato, $nome);
    }

    public function destroy($codcontrato, $nome)
    {
        ContratoAnexoService::excluir((int) $codcontrato, $nome);
        return response()->noContent();
    }
}
