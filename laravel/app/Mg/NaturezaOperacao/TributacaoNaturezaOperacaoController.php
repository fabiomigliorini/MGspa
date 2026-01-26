<?php

namespace Mg\NaturezaOperacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\NaturezaOperacao\Requests\TributacaoNaturezaOperacaoRequest;
use Mg\NaturezaOperacao\Resources\TributacaoNaturezaOperacaoResource;
use Mg\NaturezaOperacao\Resources\TributacaoNaturezaOperacaoDetailResource;

class TributacaoNaturezaOperacaoController extends Controller
{
    public function index(Request $request, int $codnaturezaoperacao)
    {
        // Verifica se a natureza de operação existe
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $query = TributacaoNaturezaOperacao::with([
            'Tributacao',
            'Cfop',
            'Estado',
            'TipoProduto',
        ])->where('codnaturezaoperacao', $codnaturezaoperacao);

        // Filtro por código exato
        if ($request->filled('codtributacaonaturezaoperacao')) {
            $query->where('codtributacaonaturezaoperacao', $request->codtributacaonaturezaoperacao);
        }

        // Filtro por tributação
        if ($request->filled('codtributacao')) {
            $query->where('codtributacao', $request->codtributacao);
        }

        // Filtro por tipo de produto
        if ($request->filled('codtipoproduto')) {
            $query->where('codtipoproduto', $request->codtipoproduto);
        }

        // Filtro por estado
        if ($request->filled('codestado')) {
            $query->where('codestado', $request->codestado);
        }

        // Filtro por NCM (busca parcial)
        if ($request->filled('ncm')) {
            $query->where('ncm', 'ilike', $request->ncm . '%');
        }

        // Filtro por CFOP
        if ($request->filled('codcfop')) {
            $query->where('codcfop', $request->codcfop);
        }

        // Ordenação
        $sortField = $request->get('sort', 'codtributacaonaturezaoperacao');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        return TributacaoNaturezaOperacaoResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codnaturezaoperacao, int $codtributacaonaturezaoperacao)
    {
        // Verifica se a natureza de operação existe
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        return new TributacaoNaturezaOperacaoDetailResource(
            TributacaoNaturezaOperacao::with([
                'Tributacao',
                'Cfop',
                'Estado',
                'TipoProduto',
                'NaturezaOperacao',
                'UsuarioCriacao',
                'UsuarioAlteracao',
            ])
            ->where('codnaturezaoperacao', $codnaturezaoperacao)
            ->findOrFail($codtributacaonaturezaoperacao)
        );
    }

    public function store(TributacaoNaturezaOperacaoRequest $request, int $codnaturezaoperacao)
    {
        // Verifica se a natureza de operação existe
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $data = $request->validated();
        $data['codnaturezaoperacao'] = $codnaturezaoperacao;

        $tributacaoNaturezaOperacao = TributacaoNaturezaOperacao::create($data);

        return (new TributacaoNaturezaOperacaoDetailResource(
            $tributacaoNaturezaOperacao->load([
                'Tributacao',
                'Cfop',
                'Estado',
                'TipoProduto',
            ])
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function update(TributacaoNaturezaOperacaoRequest $request, int $codnaturezaoperacao, int $codtributacaonaturezaoperacao)
    {
        // Verifica se a natureza de operação existe
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $tributacaoNaturezaOperacao = TributacaoNaturezaOperacao::where('codnaturezaoperacao', $codnaturezaoperacao)
            ->findOrFail($codtributacaonaturezaoperacao);

        $data = $request->validated();
        $tributacaoNaturezaOperacao->update($data);

        return new TributacaoNaturezaOperacaoDetailResource(
            $tributacaoNaturezaOperacao->fresh()->load([
                'Tributacao',
                'Cfop',
                'Estado',
                'TipoProduto',
            ])
        );
    }

    public function destroy(int $codnaturezaoperacao, int $codtributacaonaturezaoperacao)
    {
        // Verifica se a natureza de operação existe
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $tributacaoNaturezaOperacao = TributacaoNaturezaOperacao::where('codnaturezaoperacao', $codnaturezaoperacao)
            ->findOrFail($codtributacaonaturezaoperacao);

        $tributacaoNaturezaOperacao->delete();

        return response()->noContent();
    }
}
