<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;

class EstoqueSaldoRelatorioController extends MgController
{
    public function comparativoVendas(Request $request)
    {
        return $this->responder(
            EstoqueSaldoRelatorioService::comparativoVendas($request->all()),
            'comparativo-vendas',
        );
    }

    public function fisicoFiscal(Request $request)
    {
        $request->validate(['codempresa' => 'required']);
        return $this->responder(
            EstoqueSaldoRelatorioService::fisicoFiscal($request->all()),
            'fisico-fiscal',
        );
    }

    public function transferencias(Request $request)
    {
        $request->validate([
            'codestoquelocalorigem' => 'required',
            'codestoquelocaldestino' => 'required',
        ]);
        return $this->responder(
            EstoqueSaldoRelatorioService::transferencias($request->all()),
            'transferencias',
        );
    }

    private function responder(string $pdf, string $nome)
    {
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nome . '.pdf"',
        ]);
    }
}
