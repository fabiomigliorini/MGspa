<?php

namespace Mg\NaturezaOperacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\NaturezaOperacao\Cfop;
use Mg\NaturezaOperacao\Requests\CfopRequest;
use Mg\NaturezaOperacao\Resources\CfopResource;
use Mg\NaturezaOperacao\Resources\CfopDetailResource;

class CfopController extends Controller
{

    public function index(Request $request)
    {
        $query = Cfop::query();

        // Filtro por código CFOP (codcfop)
        if ($request->filled('cfop')) {
            $query->where('codcfop', 'like', $request->cfop . '%');
        }

        // Filtro por descrição
        if ($request->filled('descricao')) {
            $query->where('cfop', 'ilike', '%' . $request->descricao . '%');
        }

        // Ordenação
        $allowedSortFields = ['codcfop', 'cfop', 'descricao', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'codcfop';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        return CfopResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codcfop)
    {
        return new CfopDetailResource(
            Cfop::with([
                'UsuarioCriacao',
                'UsuarioAlteracao',
            ])->findOrFail($codcfop)
        );
    }

    public function store(CfopRequest $request)
    {
        $data = $request->validated();
        $cfop = Cfop::create($data);

        return (new CfopDetailResource($cfop))
            ->response()
            ->setStatusCode(201);
    }

    public function update(CfopRequest $request, int $codcfop)
    {
        $cfop = Cfop::findOrFail($codcfop);

        $data = $request->validated();
        $cfop->update($data);

        return new CfopDetailResource($cfop->fresh());
    }

    public function destroy(int $codcfop)
    {
        $cfop = Cfop::findOrFail($codcfop);

        // Verifica se o CFOP está em uso
        if ($cfop->NotaFiscalProdutoBarraS()->exists()) {
            abort(422, 'Não é possível excluir um CFOP que está em uso em Notas Fiscais.');
        }

        if ($cfop->TributacaoNaturezaOperacaoS()->exists()) {
            abort(422, 'Não é possível excluir um CFOP que está em uso em Tributações.');
        }

        if ($cfop->DominioAcumuladorS()->exists()) {
            abort(422, 'Não é possível excluir um CFOP que está em uso em Acumuladores do Domínio.');
        }

        $cfop->delete();

        return response()->noContent();
    }
}
