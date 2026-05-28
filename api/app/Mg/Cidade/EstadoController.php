<?php

namespace Mg\Cidade;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Cidade\Estado;
use Mg\Cidade\Pais;
use Mg\Cidade\Requests\EstadoRequest;
use Mg\Cidade\Resources\EstadoResource;
use Mg\Cidade\Resources\EstadoDetailResource;

class EstadoController extends Controller
{

    public function index(Request $request, int $codpais)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        $query = Estado::where('codpais', $codpais);

        // Ordenação
        $allowedSortFields = ['codestado', 'estado', 'sigla', 'codigooficial', 'criacao', 'alteracao'];
        $sortField = in_array($request->get('sort'), $allowedSortFields) ? $request->get('sort') : 'estado';
        $sortOrder = $request->get('order') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortOrder);

        // Estados são poucos por país, então busca todos (per_page alto)
        // para garantir que a lista completa seja exibida no frontend
        return EstadoResource::collection(
            $query->paginate($request->get('per_page', 100))
        );
    }

    public function show(int $codpais, int $codestado)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        return new EstadoDetailResource(
            Estado::with([
                'Pais',
                'UsuarioCriacao',
                'UsuarioAlteracao',
            ])->where('codpais', $codpais)
                ->where('codestado', $codestado)
                ->firstOrFail()
        );
    }

    public function store(EstadoRequest $request, int $codpais)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        $data = $request->validated();
        $data['codpais'] = $codpais;

        $estado = Estado::create($data);

        // Recarrega o modelo com os relacionamentos
        $estado = Estado::with([
            'Pais',
            'UsuarioCriacao',
            'UsuarioAlteracao',
        ])->findOrFail($estado->codestado);

        return (new EstadoDetailResource($estado))
            ->response()
            ->setStatusCode(201);
    }

    public function update(EstadoRequest $request, int $codpais, int $codestado)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        $estado = Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        $data = $request->validated();
        $estado->update($data);

        return new EstadoDetailResource($estado->fresh());
    }

    public function destroy(int $codpais, int $codestado)
    {
        // Verifica se o país existe
        Pais::findOrFail($codpais);

        $estado = Estado::where('codpais', $codpais)
            ->where('codestado', $codestado)
            ->firstOrFail();

        // Verifica se o Estado está em uso
        if ($estado->CidadeS()->exists()) {
            abort(422, 'Não é possível excluir um Estado que possui Cidades cadastradas.');
        }

        $estado->delete();

        return response()->noContent();
    }
}
