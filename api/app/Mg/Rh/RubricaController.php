<?php

namespace Mg\Rh;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mg\Usuario\Autorizador;

class RubricaController extends Controller
{
    public function index(Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $query = Rubrica::query();

        if ($request->filled('busca')) {
            $query->where('descricao', 'ilike', '%' . $request->input('busca') . '%');
        }

        $regs = $query->orderBy('descricao')->get();

        return RubricaResource::collection($regs);
    }

    public function store(RubricaStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::create($request->validated());

        return (new RubricaResource($reg))
            ->response()
            ->setStatusCode(201);
    }

    public function update(int $codrubrica, RubricaUpdateRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::findOrFail($codrubrica);
        $reg->update($request->validated());

        return new RubricaResource($reg->refresh());
    }

    public function inativar(int $codrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::findOrFail($codrubrica);
        $reg->inativo = Carbon::now();
        $reg->update();

        return new RubricaResource($reg->refresh());
    }

    public function ativar(int $codrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::findOrFail($codrubrica);
        $reg->inativo = null;
        $reg->update();

        return new RubricaResource($reg->refresh());
    }

    public function destroy(int $codrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::findOrFail($codrubrica);

        $possuiVinculo = ColaboradorRubrica::where('codrubrica', $codrubrica)->exists();
        if ($possuiVinculo) {
            abort(422, 'Rubrica vinculada a colaborador não pode ser excluída.');
        }

        $reg->delete();

        return response()->json(null, 204);
    }
}
