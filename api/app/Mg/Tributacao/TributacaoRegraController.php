<?php

namespace Mg\Tributacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Tributacao\Requests\TributacaoRegraRequest;
use Mg\Tributacao\Resources\TributacaoRegraResource;

class TributacaoRegraController extends Controller
{
    public function index(Request $request)
    {
        $query = TributacaoRegra::with([
            'Tributo',
            'NaturezaOperacao',
            'EstadoDestino',
            'CidadeDestino.Estado',
            'TipoProduto',
        ]);

        if ($request->filled('codtributo')) {
            $query->where('codtributo', $request->codtributo);
        }
        if ($request->filled('codnaturezaoperacao')) {
            $query->where('codnaturezaoperacao', $request->codnaturezaoperacao);
        }
        if ($request->filled('codtipoproduto')) {
            $query->where('codtipoproduto', $request->codtipoproduto);
        }
        if ($request->filled('ncm')) {
            $query->where('ncm', 'like', $request->ncm . '%');
        }
        if ($request->filled('codestadodestino')) {
            $query->where('codestadodestino', $request->codestadodestino);
        }
        if ($request->filled('codcidadedestino')) {
            $query->where('codcidadedestino', $request->codcidadedestino);
        }
        if ($request->filled('geracredito')) {
            $query->where('geracredito', $request->geracredito == 'true');
        }
        if ($request->filled('tipocliente')) {
            $query->where('tipocliente', $request->tipocliente);
        }
        if ($request->filled('vigencia_em')) {
            $query->whereDate('vigenciainicio', '<=', $request->vigencia_em)
                ->where(function ($q) use ($request) {
                    $q->whereNull('vigenciafim')
                        ->orWhereDate('vigenciafim', '>=', $request->vigencia_em);
                });
        }
        if ($request->filled('vigenciainicio_maior_que')) {
            $query->whereDate('vigenciainicio', '>', $request->vigenciainicio_maior_que);
        }
        if ($request->filled('vigenciafim_menor_que')) {
            $query->whereDate('vigenciafim', '<', $request->vigenciafim_menor_que);
        }
        if ($request->filled('basepercentual')) {
            $query->where('basepercentual', $request->basepercentual);
        }
        if ($request->filled('aliquota')) {
            $query->where('aliquota', $request->aliquota);
        }
        if ($request->filled('cst')) {
            $query->where('cst', $request->cst);
        }
        if ($request->filled('cclasstrib')) {
            $query->where('cclasstrib', 'like', $request->cclasstrib . '%');
        }
        if ($request->filled('beneficiocodigo')) {
            $query->where('beneficiocodigo', 'like', $request->beneficiocodigo . '%');
        }

        return TributacaoRegraResource::collection(
            $query->orderByRaw('codnaturezaoperacao DESC NULLS LAST')
                ->orderByRaw('codestadodestino DESC NULLS LAST')
                ->orderByRaw('codcidadedestino DESC NULLS LAST')
                ->orderByRaw('codtipoproduto DESC NULLS LAST')
                ->orderByRaw('tipocliente DESC NULLS LAST')
                ->orderByRaw('LENGTH(ncm) DESC NULLS LAST')
                ->orderByDesc('codtributacaoregra')
                ->paginate(20)
        );
    }

    public function show(int $id)
    {
        return new TributacaoRegraResource(
            TributacaoRegra::with([
                'Tributo', 'NaturezaOperacao', 'EstadoDestino',
                'CidadeDestino.Estado', 'TipoProduto',
            ])->findOrFail($id)
        );
    }

    public function store(TributacaoRegraRequest $request)
    {
        $reg = TributacaoRegra::create($request->validated());
        return new TributacaoRegraResource($reg);
    }

    public function update(TributacaoRegraRequest $request, int $id)
    {
        $reg = TributacaoRegra::findOrFail($id);
        $reg->update($request->validated());
        return new TributacaoRegraResource($reg);
    }

    public function destroy(int $id)
    {
        TributacaoRegra::findOrFail($id)->delete();
        return response()->noContent();
    }
}
