<?php

namespace Mg\Cidade;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Cidade\Pais;
use Mg\Cidade\Requests\PaisRequest;
use Mg\Cidade\Resources\PaisResource;
use Mg\Cidade\Resources\PaisDetailResource;

class PaisController extends Controller
{

    public function index(Request $request)
    {
        $query = Pais::query();

        // Ordenação
        $allowedSortFields = ['codpais', 'pais', 'sigla', 'codigooficial', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'pais';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        return PaisResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codpais)
    {
        return new PaisDetailResource(
            Pais::with([
                'UsuarioCriacao',
                'UsuarioAlteracao',
            ])->findOrFail($codpais)
        );
    }

    public function store(PaisRequest $request)
    {
        $data = $request->validated();
        $pais = Pais::create($data);

        return (new PaisDetailResource($pais))
            ->response()
            ->setStatusCode(201);
    }

    public function update(PaisRequest $request, int $codpais)
    {
        $pais = Pais::findOrFail($codpais);

        $data = $request->validated();
        $pais->update($data);

        return new PaisDetailResource($pais->fresh());
    }

    public function destroy(int $codpais)
    {
        $pais = Pais::findOrFail($codpais);

        // Verifica se o País está em uso
        if ($pais->EstadoS()->exists()) {
            abort(422, 'Não é possível excluir um País que possui Estados cadastrados.');
        }

        $pais->delete();

        return response()->noContent();
    }
}
