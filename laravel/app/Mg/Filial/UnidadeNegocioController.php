<?php

namespace Mg\Filial;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\Mg\Filial\CriarUnidadeNegocioRequest;
use App\Http\Requests\Mg\Filial\AtualizarUnidadeNegocioRequest;
use Mg\MgController;
use Mg\Meta\MetaUnidadeNegocio;
use Mg\Usuario\Autorizador;

class UnidadeNegocioController extends MgController
{
    public function index(Request $request)
    {
        $regs = UnidadeNegocio::orderBy('descricao')->get();
        return UnidadeNegocioResource::collection($regs);
    }

    public function show(Request $request, $codunidadenegocio)
    {
        $reg = UnidadeNegocio::findOrFail($codunidadenegocio);
        return new UnidadeNegocioResource($reg);
    }

    public function store(CriarUnidadeNegocioRequest $request)
    {
        Autorizador::autoriza(['Meta']);

        $reg = UnidadeNegocio::create($request->validated());

        return (new UnidadeNegocioResource($reg))
            ->response()
            ->setStatusCode(201);
    }

    public function update(AtualizarUnidadeNegocioRequest $request, $codunidadenegocio)
    {
        Autorizador::autoriza(['Meta']);

        $reg = UnidadeNegocio::findOrFail($codunidadenegocio);
        $reg->update($request->validated());

        return new UnidadeNegocioResource($reg);
    }

    public function destroy(Request $request, $codunidadenegocio)
    {
        Autorizador::autoriza(['Meta']);

        $reg = UnidadeNegocio::findOrFail($codunidadenegocio);

        $possuiVinculoMeta = MetaUnidadeNegocio::where('codunidadenegocio', $codunidadenegocio)->exists();
        if ($possuiVinculoMeta) {
            abort(422, 'Unidade de negocio vinculada a meta nao pode ser excluida.');
        }

        $reg->delete();

        return response()->json(null, 204);
    }

    public function inativar(Request $request, $codunidadenegocio)
    {
        Autorizador::autoriza(['Meta']);

        $reg = UnidadeNegocio::findOrFail($codunidadenegocio);
        $reg->inativo = Carbon::now();
        $reg->update();

        return new UnidadeNegocioResource($reg);
    }

    public function ativar(Request $request, $codunidadenegocio)
    {
        Autorizador::autoriza(['Meta']);

        $reg = UnidadeNegocio::findOrFail($codunidadenegocio);
        $reg->inativo = null;
        $reg->update();

        return new UnidadeNegocioResource($reg);
    }
}
