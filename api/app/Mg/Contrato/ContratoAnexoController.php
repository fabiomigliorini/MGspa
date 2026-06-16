<?php

namespace Mg\Contrato;

use Illuminate\Http\Request;
use Mg\MgController;

/**
 * Anexos (PDFs) do contrato: contrato/{codcontrato}/anexo.
 */
class ContratoAnexoController extends MgController
{
    public function index(Request $request, $codcontrato)
    {
        return response()->json(ContratoAnexoService::listagem((int) $codcontrato), 200);
    }

    public function store(Request $request, $codcontrato)
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);
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
