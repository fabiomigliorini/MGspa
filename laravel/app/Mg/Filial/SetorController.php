<?php

namespace Mg\Filial;

use Carbon\Carbon;
use Mg\MgController;
use Mg\Rh\PeriodoColaboradorSetor;
use Mg\Usuario\Autorizador;

class SetorController extends MgController
{
    public function index()
    {
        $regs = Setor::with(['UnidadeNegocio', 'TipoSetor'])
            ->orderBy('setor')
            ->get();
        return SetorResource::collection($regs);
    }

    public function store(SetorStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Setor::create($request->validated());

        return (new SetorResource($reg->load(['UnidadeNegocio', 'TipoSetor'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(SetorUpdateRequest $request, $codsetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Setor::findOrFail($codsetor);
        $reg->update($request->validated());

        return new SetorResource($reg->load(['UnidadeNegocio', 'TipoSetor']));
    }

    public function destroy($codsetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Setor::findOrFail($codsetor);

        $possuiVinculo = PeriodoColaboradorSetor::where('codsetor', $codsetor)->exists();
        if ($possuiVinculo) {
            abort(422, 'Setor vinculado a colaborador não pode ser excluído.');
        }

        $reg->delete();

        return response()->json(null, 204);
    }

    public function inativar($codsetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Setor::findOrFail($codsetor);
        $reg->inativo = Carbon::now();
        $reg->update();

        return new SetorResource($reg->load(['UnidadeNegocio', 'TipoSetor']));
    }

    public function ativar($codsetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Setor::findOrFail($codsetor);
        $reg->inativo = null;
        $reg->update();

        return new SetorResource($reg->load(['UnidadeNegocio', 'TipoSetor']));
    }
}
