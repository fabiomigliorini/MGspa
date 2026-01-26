<?php

namespace Mg\NaturezaOperacao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\NaturezaOperacao\Requests\NaturezaOperacaoRequest;
use Mg\NaturezaOperacao\Resources\NaturezaOperacaoResource;
use Mg\NaturezaOperacao\Resources\NaturezaOperacaoDetailResource;

class NaturezaOperacaoController extends Controller
{
    public function index(Request $request)
    {
        $query = NaturezaOperacao::with('Operacao');

        // Filtro por código exato
        if ($request->filled('codnaturezaoperacao')) {
            $query->where('codnaturezaoperacao', $request->codnaturezaoperacao);
        }

        // Filtro por nome da natureza operação (busca parcial)
        if ($request->filled('naturezaoperacao')) {
            $query->where('naturezaoperacao', 'ilike', '%' . $request->naturezaoperacao . '%');
        }

        // Filtro por finalidade NFe
        if ($request->filled('finnfe')) {
            $query->where('finnfe', $request->finnfe);
        }

        // Ordenação
        $sortField = $request->get('sort', 'codnaturezaoperacao');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        return NaturezaOperacaoResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codnaturezaoperacao)
    {
        return new NaturezaOperacaoDetailResource(
            NaturezaOperacao::with([
                'Operacao',
                'NaturezaOperacaoDevolucao',
                'TipoTitulo',
                'ContaContabil',
                'EstoqueMovimentoTipo',
                'UsuarioCriacao',
                'UsuarioAlteracao',
            ])->findOrFail($codnaturezaoperacao)
        );
    }

    public function store(NaturezaOperacaoRequest $request)
    {
        $data = $request->validated();

        $naturezaOperacao = NaturezaOperacao::create($data);

        return (new NaturezaOperacaoDetailResource($naturezaOperacao))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NaturezaOperacaoRequest $request, int $codnaturezaoperacao)
    {
        $naturezaOperacao = NaturezaOperacao::findOrFail($codnaturezaoperacao);

        $data = $request->validated();
        $naturezaOperacao->update($data);

        return new NaturezaOperacaoDetailResource($naturezaOperacao->fresh());
    }

    public function destroy(int $codnaturezaoperacao)
    {
        $naturezaOperacao = NaturezaOperacao::findOrFail($codnaturezaoperacao);

        // Validação de dependências
        if ($naturezaOperacao->NotaFiscalS()->exists()) {
            abort(422, 'Não é possível excluir uma Natureza de Operação que possui Notas Fiscais vinculadas.');
        }

        if ($naturezaOperacao->NegocioS()->exists()) {
            abort(422, 'Não é possível excluir uma Natureza de Operação que possui Negócios vinculados.');
        }

        if ($naturezaOperacao->NaturezaOperacaoS()->exists()) {
            abort(422, 'Não é possível excluir uma Natureza de Operação que está vinculada como devolução de outra.');
        }

        $naturezaOperacao->delete();

        return response()->noContent();
    }
}
