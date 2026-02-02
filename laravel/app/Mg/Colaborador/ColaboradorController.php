<?php

namespace Mg\Colaborador;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ColaboradorController extends MgController
{

    public function create(Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $data = $request->all();
        $criarcolaborador = ColaboradorService::create($data);
        return new ColaboradorResource($criarcolaborador);
    }

    public function show(Request $request, $codpessoa)
    {
        $colaborador = Colaborador::where('codpessoa', $codpessoa)->get();
        foreach ($colaborador as $col) {
            if (empty($col->googledrivefolderid)) {
                ColaboradorService::criarFolderGoogleDrive($col);
            }
        }
        return ColaboradorResource::collection($colaborador);
    }

    public function update(Request $request, $codcolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $data = $request->all();
        $colaborador = Colaborador::findOrFail($codcolaborador);
        $colaborador = ColaboradorService::update($colaborador, $data);

        return new ColaboradorResource($colaborador);
    }

    public function delete(Request $request, $codcolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $colaborador = Colaborador::findOrFail($codcolaborador);
        $colaborador = ColaboradorService::delete($colaborador);

        return response()->json([
            'result' => true
        ], 200);
    }
}
