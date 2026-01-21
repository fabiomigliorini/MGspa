<?php

namespace Mg\Tributacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Tributacao\Tributacao;
use Mg\Tributacao\Requests\TributacaoRequest;
use Mg\Tributacao\Resources\TributacaoResource;
use Mg\Tributacao\Resources\TributacaoDetailResource;

class TributacaoController extends Controller
{
    public function index(Request $request)
    {
        $query = Tributacao::query();

        // Filtro por nome da tributacao
        if ($request->filled('tributacao')) {
            $query->where('tributacao', 'ilike', '%' . $request->tributacao . '%');
        }

        // Ordenacao
        $sortField = $request->get('sort', 'tributacao');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        return TributacaoResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codtributacao)
    {
        return new TributacaoDetailResource(
            Tributacao::with([
                'UsuarioCriacao',
                'UsuarioAlteracao',
            ])->findOrFail($codtributacao)
        );
    }

    public function store(TributacaoRequest $request)
    {
        $data = $request->validated();

        $tributacao = Tributacao::create($data);

        return (new TributacaoDetailResource($tributacao))
            ->response()
            ->setStatusCode(201);
    }

    public function update(TributacaoRequest $request, int $codtributacao)
    {
        $tributacao = Tributacao::findOrFail($codtributacao);

        $data = $request->validated();
        $tributacao->update($data);

        return new TributacaoDetailResource($tributacao->fresh());
    }

    public function destroy(int $codtributacao)
    {
        $tributacao = Tributacao::findOrFail($codtributacao);

        $tributacao->delete();

        return response()->noContent();
    }
}
