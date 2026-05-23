<?php

namespace Mg\Cidade;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Cidade\Requests\EstadoRequest;
use Mg\Cidade\Resources\EstadoDetailResource;
use Mg\Cidade\Resources\EstadoResource;

class EstadoController extends Controller
{
    public function index(Request $request, int $codpais)
    {
        Pais::findOrFail($codpais);

        $query = Estado::where('codpais', $codpais);

        $allowedSortFields = ['codestado', 'estado', 'sigla', 'codigooficial', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'estado';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        return EstadoResource::collection($query->paginate($request->get('per_page', 100)));
    }

    public function show(int $codpais, int $codestado)
    {
        Pais::findOrFail($codpais);

        return new EstadoDetailResource(
            Estado::with(['Pais', 'UsuarioCriacao', 'UsuarioAlteracao'])
                ->where('codpais', $codpais)
                ->where('codestado', $codestado)
                ->firstOrFail()
        );
    }

    public function store(EstadoRequest $request, int $codpais)
    {
        Pais::findOrFail($codpais);

        $data = $request->validated();
        $data['codpais'] = $codpais;

        $estado = Estado::create($data);
        $estado = Estado::with(['Pais', 'UsuarioCriacao', 'UsuarioAlteracao'])->findOrFail($estado->codestado);

        return (new EstadoDetailResource($estado))->response()->setStatusCode(201);
    }

    public function update(EstadoRequest $request, int $codpais, int $codestado)
    {
        Pais::findOrFail($codpais);

        $estado = Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();
        $estado->update($request->validated());

        return new EstadoDetailResource($estado->fresh());
    }

    public function destroy(int $codpais, int $codestado)
    {
        Pais::findOrFail($codpais);

        $estado = Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        if ($estado->CidadeS()->exists()) {
            abort(422, 'Não é possível excluir um Estado que possui Cidades cadastradas.');
        }

        $estado->delete();
        return response()->noContent();
    }
}
