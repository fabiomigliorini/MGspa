<?php

namespace Mg\Cidade;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Cidade\Cidade;
use Mg\Cidade\Estado;
use Mg\Cidade\Pais;
use Mg\Cidade\Requests\CidadeRequest;
use Mg\Cidade\Resources\CidadeResource;
use Mg\Cidade\Resources\CidadeDetailResource;

class CidadeController extends Controller
{

    public function index(Request $request, int $codpais, int $codestado)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        // Verifica se o estado existe e pertence ao país
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $query = Cidade::where('codestado', $codestado);

        // Filtro por nome da cidade
        if ($request->filled('cidade')) {
            $query->where('cidade', 'ilike', '%' . $request->cidade . '%');
        }

        // Filtro por código oficial
        if ($request->filled('codigooficial')) {
            $query->where('codigooficial', $request->codigooficial);
        }

        // Ordenação
        $sortField = $request->get('sort', 'cidade');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        return CidadeResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codpais, int $codestado, int $codcidade)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        // Verifica se o estado existe e pertence ao país
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        return new CidadeDetailResource(
            Cidade::with([
                'Estado.Pais',
                'UsuarioCriacao',
                'UsuarioAlteracao',
            ])->where('codestado', $codestado)
                ->where('codcidade', $codcidade)
                ->firstOrFail()
        );
    }

    public function store(CidadeRequest $request, int $codpais, int $codestado)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        // Verifica se o estado existe e pertence ao país
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $data = $request->validated();
        $data['codestado'] = $codestado;

        $cidade = Cidade::create($data);

        return (new CidadeDetailResource($cidade))
            ->response()
            ->setStatusCode(201);
    }

    public function update(CidadeRequest $request, int $codpais, int $codestado, int $codcidade)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        // Verifica se o estado existe e pertence ao país
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $cidade = Cidade::where('codestado', $codestado)
            ->where('codcidade', $codcidade)
            ->firstOrFail();

        $data = $request->validated();
        $cidade->update($data);

        return new CidadeDetailResource($cidade->fresh());
    }

    public function destroy(int $codpais, int $codestado, int $codcidade)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        // Verifica se o estado existe e pertence ao país
        Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $cidade = Cidade::where('codestado', $codestado)
            ->where('codcidade', $codcidade)
            ->firstOrFail();

        // Verifica se a Cidade está em uso
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
