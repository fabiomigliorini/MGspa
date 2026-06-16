<?php

namespace Mg\Fazenda;

use App\Http\Requests\Mg\Fazenda\PlantioStoreRequest;
use App\Http\Requests\Mg\Fazenda\PlantioUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;

/**
 * Plantio e aninhado na safra: safra/{codsafra}/plantio/{codplantio}.
 * O codsafra vem sempre da URL (contexto), nunca do corpo da requisicao.
 */
class PlantioController extends MgController
{
    const WITH = ['Safra.Cultura', 'Fazenda', 'Variedade'];

    public function index(Request $request, $codsafra)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $filter['codsafra'] = $codsafra;
        $res = PlantioService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return PlantioResource::collection($res);
    }

    public function store(PlantioStoreRequest $request, $codsafra)
    {
        $model = new Plantio();
        $model->fill($request->validated());
        $model->codsafra = $codsafra;
        $model->save();

        return new PlantioResource($model->fresh(static::WITH));
    }

    public function show(Request $request, $codsafra, $codplantio)
    {
        return new PlantioResource($this->buscar($codsafra, $codplantio, static::WITH));
    }

    public function update(PlantioUpdateRequest $request, $codsafra, $codplantio)
    {
        $model = $this->buscar($codsafra, $codplantio);

        $model->fill($request->validated());
        $model->codsafra = $codsafra;
        $model->update();

        return new PlantioResource($model->fresh(static::WITH));
    }

    public function destroy($codsafra, $codplantio)
    {
        $this->buscar($codsafra, $codplantio)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $codsafra, $codplantio)
    {
        $model = $this->buscar($codsafra, $codplantio);
        PlantioService::inativar($model);
        return new PlantioResource($model->fresh(static::WITH));
    }

    public function ativar(Request $request, $codsafra, $codplantio)
    {
        $model = $this->buscar($codsafra, $codplantio);
        PlantioService::ativar($model);
        return new PlantioResource($model->fresh(static::WITH));
    }

    protected function buscar($codsafra, $codplantio, array $with = []): Plantio
    {
        return Plantio::with($with)->where('codsafra', $codsafra)->findOrFail($codplantio);
    }
}
