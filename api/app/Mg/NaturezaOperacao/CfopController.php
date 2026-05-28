<?php

namespace Mg\NaturezaOperacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\NaturezaOperacao\Requests\CfopRequest;
use Mg\NaturezaOperacao\Resources\CfopDetailResource;
use Mg\NaturezaOperacao\Resources\CfopResource;

class CfopController extends Controller
{
    public function index(Request $request)
    {
        $query = Cfop::query();

        if ($request->filled('cfop')) {
            $query->where('codcfop', 'like', $request->cfop . '%');
        }
        if ($request->filled('descricao')) {
            $query->where('cfop', 'ilike', '%' . $request->descricao . '%');
        }

        $allowedSortFields = ['codcfop', 'cfop', 'descricao', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'codcfop';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        return CfopResource::collection($query->paginate($request->get('per_page', 20)));
    }

    public function show(int $codcfop)
    {
        return new CfopDetailResource(
            Cfop::with(['UsuarioCriacao', 'UsuarioAlteracao'])->findOrFail($codcfop)
        );
    }

    public function store(CfopRequest $request)
    {
        $reg = Cfop::create($request->validated());
        return (new CfopDetailResource($reg))->response()->setStatusCode(201);
    }

    public function update(CfopRequest $request, int $codcfop)
    {
        $reg = Cfop::findOrFail($codcfop);
        $reg->update($request->validated());
        return new CfopDetailResource($reg->fresh());
    }

    public function destroy(int $codcfop)
    {
        $reg = Cfop::findOrFail($codcfop);

        if ($reg->NotaFiscalProdutoBarraS()->exists()) {
            abort(422, 'Não é possível excluir um CFOP que está em uso em Notas Fiscais.');
        }
        if ($reg->TributacaoNaturezaOperacaoS()->exists()) {
            abort(422, 'Não é possível excluir um CFOP que está em uso em Tributações.');
        }
        if ($reg->DominioAcumuladorS()->exists()) {
            abort(422, 'Não é possível excluir um CFOP que está em uso em Acumuladores do Domínio.');
        }

        $reg->delete();
        return response()->noContent();
    }
}
