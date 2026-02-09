<?php

namespace Mg\Pessoa;

use App\Http\Requests\Mg\Pessoa\DependenteStoreRequest;
use App\Http\Requests\Mg\Pessoa\DependenteUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class DependenteController extends MgController
{

    public function create(DependenteStoreRequest $request)
    {
        Autorizador::autoriza(['Publico']);

        $dependente = DependenteService::create($request->validated());

        return new DependenteResource($dependente);
    }

    public function update(DependenteUpdateRequest $request, $coddependente)
    {
        Autorizador::autoriza(['Publico']);

        $dependente = Dependente::findOrFail($coddependente);
        $dependente = DependenteService::update($dependente, $request->validated());

        return new DependenteResource($dependente);
    }

    public function delete(Request $request, $coddependente)
    {
        Autorizador::autoriza(['Publico']);

        $dependente = Dependente::findOrFail($coddependente);

        $result = DependenteService::delete($dependente);

        return response()->json([
            'result' => $result
        ], 200);
    }

    public function ativar(Request $request, $coddependente)
    {
        Autorizador::autoriza(['Publico']);

        $dependente = Dependente::findOrFail($coddependente);

        $dependente = DependenteService::ativar($dependente);

        return new DependenteResource($dependente);
    }

    public function inativar(Request $request, $coddependente)
    {
        Autorizador::autoriza(['Publico']);

        $dependente = Dependente::findOrFail($coddependente);

        $dependente = DependenteService::inativar($dependente);

        return new DependenteResource($dependente);
    }
}
