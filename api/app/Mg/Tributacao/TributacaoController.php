<?php

namespace Mg\Tributacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Tributacao\Requests\TributacaoRequest;
use Mg\Tributacao\Resources\TributacaoDetailResource;
use Mg\Tributacao\Resources\TributacaoResource;

class TributacaoController extends Controller
{
    public function index(Request $request)
    {
        $query = Tributacao::query();

        if ($request->filled('tributacao')) {
            $query->where('tributacao', 'ilike', '%' . $request->tributacao . '%');
        }

        $allowedSortFields = ['codtributacao', 'tributacao', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'tributacao';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        return TributacaoResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codtributacao)
    {
        return new TributacaoDetailResource(
            Tributacao::with(['UsuarioCriacao', 'UsuarioAlteracao'])
                ->findOrFail($codtributacao)
        );
    }

    public function store(TributacaoRequest $request)
    {
        $reg = Tributacao::create($request->validated());
        return (new TributacaoDetailResource($reg))->response()->setStatusCode(201);
    }

    public function update(TributacaoRequest $request, int $codtributacao)
    {
        $reg = Tributacao::findOrFail($codtributacao);
        $reg->update($request->validated());
        return new TributacaoDetailResource($reg->fresh());
    }

    public function destroy(int $codtributacao)
    {
        Tributacao::findOrFail($codtributacao)->delete();
        return response()->noContent();
    }
}
