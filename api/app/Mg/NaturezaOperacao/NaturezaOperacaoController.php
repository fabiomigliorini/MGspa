<?php

namespace Mg\NaturezaOperacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\NaturezaOperacao\Requests\NaturezaOperacaoRequest;
use Mg\NaturezaOperacao\Resources\NaturezaOperacaoDetailResource;
use Mg\NaturezaOperacao\Resources\NaturezaOperacaoResource;

class NaturezaOperacaoController extends Controller
{
    public function index(Request $request)
    {
        $query = NaturezaOperacao::with('Operacao');

        if ($request->filled('codnaturezaoperacao')) {
            $query->where('codnaturezaoperacao', $request->codnaturezaoperacao);
        }
        if ($request->filled('naturezaoperacao')) {
            $query->where('naturezaoperacao', 'ilike', '%' . $request->naturezaoperacao . '%');
        }
        if ($request->filled('finnfe')) {
            $query->where('finnfe', $request->finnfe);
        }
        if ($request->filled('codoperacao')) {
            $query->where('codoperacao', $request->codoperacao);
        }

        $allowedSortFields = ['codnaturezaoperacao', 'naturezaoperacao', 'codoperacao', 'emitida', 'finnfe', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'codnaturezaoperacao';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        return NaturezaOperacaoResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codnaturezaoperacao)
    {
        return new NaturezaOperacaoDetailResource(
            NaturezaOperacao::with([
                'Operacao', 'NaturezaOperacaoDevolucao', 'TipoTitulo',
                'ContaContabil', 'EstoqueMovimentoTipo',
                'UsuarioCriacao', 'UsuarioAlteracao',
            ])->findOrFail($codnaturezaoperacao)
        );
    }

    public function store(NaturezaOperacaoRequest $request)
    {
        $reg = NaturezaOperacao::create($request->validated());
        return (new NaturezaOperacaoDetailResource($reg))->response()->setStatusCode(201);
    }

    public function update(NaturezaOperacaoRequest $request, int $codnaturezaoperacao)
    {
        $reg = NaturezaOperacao::findOrFail($codnaturezaoperacao);
        $reg->update($request->validated());
        return new NaturezaOperacaoDetailResource($reg->fresh());
    }

    public function destroy(int $codnaturezaoperacao)
    {
        $reg = NaturezaOperacao::findOrFail($codnaturezaoperacao);

        if ($reg->NotaFiscalS()->exists()) {
            abort(422, 'Não é possível excluir uma Natureza de Operação que possui Notas Fiscais vinculadas.');
        }
        if ($reg->NegocioS()->exists()) {
            abort(422, 'Não é possível excluir uma Natureza de Operação que possui Negócios vinculados.');
        }
        if ($reg->NaturezaOperacaoS()->exists()) {
            abort(422, 'Não é possível excluir uma Natureza de Operação que está vinculada como devolução de outra.');
        }

        $reg->delete();
        return response()->noContent();
    }
}
