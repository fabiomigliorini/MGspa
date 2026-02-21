<?php

namespace Mg\Filial;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class TipoSetorController extends MgController
{
    public function index(Request $request)
    {
        $regs = TipoSetor::orderBy('tiposetor')->get();
        return TipoSetorResource::collection($regs);
    }

    public function store(Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $data = $request->validate([
            'tiposetor' => ['required', 'string', 'max:100'],
        ]);

        $reg = TipoSetor::create($data);

        return (new TipoSetorResource($reg))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, $codtiposetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $data = $request->validate([
            'tiposetor' => ['required', 'string', 'max:100'],
        ]);

        $reg = TipoSetor::findOrFail($codtiposetor);
        $reg->update($data);

        return new TipoSetorResource($reg);
    }

    public function destroy(Request $request, $codtiposetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = TipoSetor::findOrFail($codtiposetor);

        $possuiVinculo = Setor::where('codtiposetor', $codtiposetor)->exists();
        if ($possuiVinculo) {
            abort(422, 'Tipo de setor vinculado a setor não pode ser excluído.');
        }

        $reg->delete();

        return response()->json(null, 204);
    }

    public function inativar(Request $request, $codtiposetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = TipoSetor::findOrFail($codtiposetor);
        $reg->inativo = Carbon::now();
        $reg->update();

        return new TipoSetorResource($reg);
    }

    public function ativar(Request $request, $codtiposetor)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = TipoSetor::findOrFail($codtiposetor);
        $reg->inativo = null;
        $reg->update();

        return new TipoSetorResource($reg);
    }
}
