<?php

namespace Mg\Filial;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

// Percentuais do Simples por mes da filial (TASK-280). A filial e' a rota,
// nunca o payload.
class FilialSimplesController extends Controller
{
    public function index(int $codfilial)
    {
        return FilialSimplesResource::collection(FilialSimplesService::listar($codfilial));
    }

    // O mes da competencia, para a nota de servico. Mes sem cadastro devolve
    // data null (nao e' erro: a nota fica com os campos vazios e avisa).
    public function mes(int $codfilial, string $competencia)
    {
        $reg = FilialSimplesService::doMes($codfilial, $competencia);
        return response()->json(['data' => $reg ? new FilialSimplesResource($reg) : null]);
    }

    public function store(int $codfilial, FilialSimplesRequest $request)
    {
        $reg = DB::transaction(fn () => FilialSimplesService::criar($codfilial, $request->validated()));
        return (new FilialSimplesResource($reg->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(int $codfilial, int $codfilialsimples, FilialSimplesRequest $request)
    {
        $reg = DB::transaction(
            fn () => FilialSimplesService::atualizar($codfilial, $codfilialsimples, $request->validated())
        );
        return new FilialSimplesResource($reg->refresh());
    }

    public function destroy(int $codfilial, int $codfilialsimples)
    {
        DB::transaction(fn () => FilialSimplesService::excluir($codfilial, $codfilialsimples));
        return response()->noContent();
    }
}
