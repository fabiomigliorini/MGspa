<?php

namespace Mg\Vale;

use App\Http\Requests\Mg\Vale\ValeModeloStoreRequest;
use App\Http\Requests\Mg\Vale\ValeModeloUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\MgController;
use Mg\MgService;
use Mg\Usuario\Autorizador;

class ValeModeloController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        [$filter, $sort, $fields] = $this->filtros($request);
        // So o cabecalho: a listagem nao mostra nada dos itens, e o
        // formulario e outra rota, que chama o show.
        $res = ValeModeloService::pesquisar($filter, $sort, $fields)
            ->paginate()
            ->appends($request->all());

        return ValeModeloListagemResource::collection($res);
    }

    public function show(Request $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        return new ValeModeloResource(
            ValeModelo::with([
                'PessoaFavorecido',
                'ValeModeloProdutoBarraS.ProdutoBarra.Produto.UnidadeMedida',
                'ValeModeloProdutoBarraS.ProdutoBarra.ProdutoVariacao.ProdutoImagem',
                'ValeModeloProdutoBarraS.ProdutoBarra.ProdutoEmbalagem.UnidadeMedida',
            ])->findOrFail($valeModelo)
        );
    }

    public function store(ValeModeloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = DB::transaction(function () use ($request) {
            return ValeModeloService::salvar($request->validated());
        });

        return new ValeModeloResource($model);
    }

    public function update(ValeModeloUpdateRequest $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ValeModelo::findOrFail($valeModelo);
        $model = DB::transaction(function () use ($request, $model) {
            return ValeModeloService::salvar($request->validated(), $model);
        });

        return new ValeModeloResource($model);
    }

    public function destroy($valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ValeModelo::findOrFail($valeModelo);
        try {
            DB::transaction(function () use ($model) {
                $model->ValeModeloProdutoBarraS()->delete();
                $model->delete();
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) !== '23503') {
                throw $e;
            }
            abort(409, 'Existem vales emitidos com este Modelo! Inative em vez de excluir!');
        }

        return response()->noContent();
    }

    /**
     * Impressao do modelo em A4, para a escola conferir itens e valores antes
     * da temporada.
     *
     * ?html=1 devolve o HTML cru -- e como se ajusta o layout sem re-renderizar
     * o PDF a cada tentativa (mesmo contrato do relatorio de romaneios).
     */
    public function relatorio(Request $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        $modelo = ValeModelo::findOrFail($valeModelo);

        if ($request->boolean('html')) {
            return response(ValeModeloRelatorioService::html($modelo), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return response(ValeModeloRelatorioService::pdf($modelo), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="modelo-vale-'
                . $modelo->codvalemodelo . '.pdf"',
        ]);
    }

    public function inativar(Request $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        MgService::inativar(ValeModelo::findOrFail($valeModelo));
        return new ValeModeloResource(ValeModelo::findOrFail($valeModelo));
    }

    public function ativar(Request $request, $valeModelo)
    {
        Autorizador::autoriza(self::GRUPOS);

        MgService::ativar(ValeModelo::findOrFail($valeModelo));
        return new ValeModeloResource(ValeModelo::findOrFail($valeModelo));
    }
}
