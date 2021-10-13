<?php

namespace Mg\Portador;

use Illuminate\Http\Request;

use Mg\MgController;

class PortadorController extends MgController
{

    public function index(Request $request)
    {
        $portadores = Portador::ativo()->orderBy('portador')->get();
        return PortadorResource::collection($portadores);
    }

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
        $ret = [];
        foreach ($request->arquivos as $key => $arquivo) {
            $ofx = file_get_contents($arquivo->getRealPath());
            $ret[$arquivo->getClientOriginalName()] = PortadorService::importarOfx($ofx);
        }
        return $ret;
    }

}
