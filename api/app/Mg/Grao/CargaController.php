<?php

namespace Mg\Grao;

use App\Http\Requests\Mg\Grao\CargaSincronizarRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class CargaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = CargaService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return CargaResource::collection($res);
    }

    /**
     * Listagem de romaneios (tela /cargas do agro) — historico no servidor, com
     * os filtros amplos de CargaService::qryFiltros.
     *
     * Separada do index() de proposito: aquele e o pull do patio offline e nao
     * pode mudar de forma. Aqui o eager load e enxuto (WITH_LISTAGEM) e os
     * totais vem do RECORTE INTEIRO, nao da pagina — quem filtra "setembro" quer
     * o total de setembro, nao o das 50 primeiras linhas.
     */
    public function listagem(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = CargaService::pesquisar($filter, $sort, $fields, CargaService::WITH_LISTAGEM);

        $totais = CargaService::totais($filter);

        $res = $qry->paginate($request->integer('per_page') ?: null)->appends($request->all());

        return CargaListagemResource::collection($res)->additional(['totais' => $totais]);
    }

    public function show(Request $request, $id)
    {
        return new CargaResource(Carga::with(CargaService::WITH)->findOrFail($id));
    }

    /**
     * Relatorio PDF dos romaneios do recorte. `?html=1` devolve o HTML cru —
     * e como se ajusta o layout em mm sem re-renderizar PDF a cada tentativa.
     */
    public function relatorio(Request $request)
    {
        $filtros = $request->all();

        if ($request->boolean('html')) {
            return response(CargaRelatorioService::html($filtros), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return response(CargaRelatorioService::pdf($filtros), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="romaneios.pdf"',
        ]);
    }

    /**
     * Sincroniza uma carga criada/editada offline (upsert por uuid). Aceita
     * parcial — pesos/classificacao/pontos chegam ao longo das etapas do patio.
     */
    public function sincronizar(CargaSincronizarRequest $request)
    {
        $carga = CargaService::sincronizar($request->validated());
        return new CargaResource($carga->load(CargaService::WITH));
    }

    public function inativar(Request $request, $id)
    {
        $model = CargaService::inativar(Carga::findOrFail($id));
        return new CargaResource($model->fresh(CargaService::WITH));
    }

    public function ativar(Request $request, $id)
    {
        $model = CargaService::ativar(Carga::findOrFail($id));
        return new CargaResource($model->fresh(CargaService::WITH));
    }
}
