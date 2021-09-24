<?php

namespace Mg\Portador;

use Illuminate\Http\Request;

use Mg\MgController;

class PortadorController extends MgController
{

    public function show(Request $request, $codportador)
    {
        $portador = Portador::findOrFail($codportador);
        return new PortadorResource($portador);
    }

    public function importarOfx(Request $request)
    {
        $request->validate([
            'arquivos' => 'required',
            'arquivos.*' => 'required|mimes:txt,ofx'
        ],[
            'arquivos.required' => 'Nenhum arquivo enviado!',
            'arquivos.*.required' => 'Envie um arquivo!',
            'arquivos.*.mimes' => 'Somente arquivos OFX aceitos!',
        ]);
        foreach ($request->arquivos as $key => $arquivo) {
            $ofx = file_get_contents($arquivo->getRealPath());
            $ret = PortadorService::importarOfx($ofx);
        }
        return $ret;
    }

}
