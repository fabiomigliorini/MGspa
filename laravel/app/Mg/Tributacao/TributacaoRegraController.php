<?php

namespace Mg\Tributacao;

use App\Http\Controllers\Controller;
use Mg\Tributacao\Requests\TributacaoRegraRequest;
use Mg\Tributacao\Resources\TributacaoRegraResource;
use Illuminate\Http\Request;

class TributacaoRegraController extends Controller
{
    public function index(Request $request)
    {
        $query = TributacaoRegra::with('Tributo');

        if ($request->filled('codtributo')) {
            $query->where('codtributo', $request->codtributo);
        }

        if ($request->filled('codnaturezaoperacao')) {
            $query->where('codnaturezaoperacao', $request->codnaturezaoperacao);
        }

        if ($request->filled('ncm')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('ncm')
                  ->orWhere('ncm', 'like', $request->ncm . '%');
            });
        }

        if ($request->filled('codestado')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('codestadodestino')
                  ->orWhere('codestadodestino', $request->codestado);
            });
        }

        if ($request->filled('codcidade')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('codcidadedestino')
                  ->orWhere('codcidadedestino', $request->codcidade);
            });
        }

        if ($request->filled('vigencia_em')) {
            $query->whereDate('vigenciainicio', '<=', $request->vigencia_em)
                  ->where(function ($q) use ($request) {
                      $q->whereNull('vigenciafim')
                        ->orWhereDate('vigenciafim', '>=', $request->vigencia_em);
                  });
        }

        return TributacaoRegraResource::collection(
            $query->orderByDesc('codtributacaoregra')->paginate(20)
        );
    }

    public function show(int $id)
    {
        return new TributacaoRegraResource(
            TributacaoRegra::with('Tributo')->findOrFail($id)
        );
    }

    public function store(TributacaoRegraRequest $request)
    {
        // dd($request->all());
        $regra = TributacaoRegra::create($request->validated());
        return new TributacaoRegraResource($regra);
    }

    public function update(TributacaoRegraRequest $request, int $id)
    {
        $regra = TributacaoRegra::findOrFail($id);
        $regra->update($request->validated());
        return new TributacaoRegraResource($regra);
    }

    public function destroy(int $id)
    {
        TributacaoRegra::findOrFail($id)->delete();
        return response()->noContent();
    }
}
