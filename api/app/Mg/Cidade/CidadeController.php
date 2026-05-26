<?php

namespace Mg\Cidade;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Cidade\Requests\CidadeRequest;
use Mg\Cidade\Resources\CidadeDetailResource;
use Mg\Cidade\Resources\CidadeResource;

class CidadeController extends Controller
{
    public function index(Request $request, int $codpais, int $codestado)
    {
        Pais::findOrFail($codpais);
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $query = Cidade::where('codestado', $codestado);

        if ($request->filled('cidade')) {
            $query->where('cidade', 'ilike', '%' . $request->cidade . '%');
        }
        if ($request->filled('codigooficial')) {
            $query->where('codigooficial', $request->codigooficial);
        }

        $allowedSortFields = ['codcidade', 'cidade', 'codigooficial', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'cidade';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        return CidadeResource::collection($query->paginate($request->get('per_page', 20)));
    }

    public function show(int $codpais, int $codestado, int $codcidade)
    {
        Pais::findOrFail($codpais);
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        return new CidadeDetailResource(
            Cidade::with(['Estado.Pais', 'UsuarioCriacao', 'UsuarioAlteracao'])
                ->where('codestado', $codestado)
                ->where('codcidade', $codcidade)
                ->firstOrFail()
        );
    }

    public function store(CidadeRequest $request, int $codpais, int $codestado)
    {
        Pais::findOrFail($codpais);
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $data = $request->validated();
        $data['codestado'] = $codestado;

        $cidade = Cidade::create($data);

        return (new CidadeDetailResource($cidade))->response()->setStatusCode(201);
    }

    public function update(CidadeRequest $request, int $codpais, int $codestado, int $codcidade)
    {
        Pais::findOrFail($codpais);
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $cidade = Cidade::where('codestado', $codestado)
            ->where('codcidade', $codcidade)
            ->firstOrFail();
        $cidade->update($request->validated());

        return new CidadeDetailResource($cidade->fresh());
    }

    public function destroy(int $codpais, int $codestado, int $codcidade)
    {
        Pais::findOrFail($codpais);
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $cidade = Cidade::where('codestado', $codestado)
            ->where('codcidade', $codcidade)
            ->firstOrFail();

        if ($cidade->PessoaS()->exists()) {
            abort(422, 'Não é possível excluir uma Cidade que possui Pessoas cadastradas.');
        }
        if ($cidade->PessoaCobrancaS()->exists()) {
            abort(422, 'Não é possível excluir uma Cidade que está em uso como endereço de cobrança.');
        }
        if ($cidade->PessoaEnderecoS()->exists()) {
            abort(422, 'Não é possível excluir uma Cidade que está em uso em endereços de pessoas.');
        }

        $cidade->delete();
        return response()->noContent();
    }
}
