<?php

namespace Mg\NaturezaOperacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\NaturezaOperacao\Requests\TributacaoNaturezaOperacaoRequest;
use Mg\NaturezaOperacao\Resources\TributacaoNaturezaOperacaoDetailResource;
use Mg\NaturezaOperacao\Resources\TributacaoNaturezaOperacaoResource;

class TributacaoNaturezaOperacaoController extends Controller
{
    public function index(Request $request, int $codnaturezaoperacao)
    {
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $query = TributacaoNaturezaOperacao::with(['Tributacao', 'Cfop', 'Estado', 'TipoProduto'])
            ->where('codnaturezaoperacao', $codnaturezaoperacao);

        foreach (['codtributacaonaturezaoperacao', 'codtributacao', 'codtipoproduto', 'codestado', 'codcfop'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->$f);
            }
        }
        if ($request->filled('ncm')) {
            $query->where('ncm', 'ilike', $request->ncm . '%');
        }
        if ($request->has('bit') && $request->input('bit') !== null && $request->input('bit') !== '') {
            $bv = $request->input('bit');
            $query->where('bit', (bool) (is_string($bv) ? in_array($bv, ['true', '1'], true) : $bv));
        }

        $allowedSortFields = ['codtributacaonaturezaoperacao', 'codtributacao', 'codtipoproduto', 'codestado', 'ncm', 'codcfop', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'codtributacaonaturezaoperacao';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        return TributacaoNaturezaOperacaoResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codnaturezaoperacao, int $codtributacaonaturezaoperacao)
    {
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        return new TributacaoNaturezaOperacaoDetailResource(
            TributacaoNaturezaOperacao::with([
                'Tributacao', 'Cfop', 'Estado', 'TipoProduto',
                'NaturezaOperacao', 'UsuarioCriacao', 'UsuarioAlteracao',
            ])->where('codnaturezaoperacao', $codnaturezaoperacao)
                ->findOrFail($codtributacaonaturezaoperacao)
        );
    }

    public function store(TributacaoNaturezaOperacaoRequest $request, int $codnaturezaoperacao)
    {
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $data = $request->validated();
        $data['codnaturezaoperacao'] = $codnaturezaoperacao;
        $reg = TributacaoNaturezaOperacao::create($data);

        return (new TributacaoNaturezaOperacaoDetailResource(
            $reg->load(['Tributacao', 'Cfop', 'Estado', 'TipoProduto'])
        ))->response()->setStatusCode(201);
    }

    public function update(TributacaoNaturezaOperacaoRequest $request, int $codnaturezaoperacao, int $codtributacaonaturezaoperacao)
    {
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $reg = TributacaoNaturezaOperacao::where('codnaturezaoperacao', $codnaturezaoperacao)
            ->findOrFail($codtributacaonaturezaoperacao);
        $reg->update($request->validated());

        return new TributacaoNaturezaOperacaoDetailResource(
            $reg->fresh()->load(['Tributacao', 'Cfop', 'Estado', 'TipoProduto'])
        );
    }

    public function destroy(int $codnaturezaoperacao, int $codtributacaonaturezaoperacao)
    {
        NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $reg = TributacaoNaturezaOperacao::where('codnaturezaoperacao', $codnaturezaoperacao)
            ->findOrFail($codtributacaonaturezaoperacao);
        $reg->delete();

        return response()->noContent();
    }
}
