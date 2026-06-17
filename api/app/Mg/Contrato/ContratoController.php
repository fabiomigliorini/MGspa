<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoCalculoRequest;
use App\Http\Requests\Mg\Contrato\ContratoStoreRequest;
use App\Http\Requests\Mg\Contrato\ContratoUpdateRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class ContratoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = ContratoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return ContratoResource::collection($res);
    }

    public function show(Request $request, $id)
    {
        return new ContratoResource(ContratoService::detalhe((int) $id));
    }

    /**
     * Preview do preço líquido (deduções FETHAB/IAGRO/Senar/Funrural) p/ a tela
     * de contrato — sem persistir. O agro é o dono do cálculo.
     */
    public function calculo(ContratoCalculoRequest $request)
    {
        return response()->json(ContratoCalculoService::calcular([
            'codcultura' => (int) $request->codcultura,
            'bruto' => (float) $request->bruto,
            'data' => $request->data,
            'isentofethab' => $request->boolean('isentofethab'),
            'funruralvenda' => $request->boolean('funruralvenda'),
        ]), 200);
    }

    public function store(ContratoStoreRequest $request)
    {
        $model = ContratoService::salvar($request->validated());
        return new ContratoResource(ContratoService::detalhe((int) $model->codcontrato));
    }

    public function update(ContratoUpdateRequest $request, $id)
    {
        $model = ContratoService::salvar($request->validated(), Contrato::findOrFail($id));
        return new ContratoResource(ContratoService::detalhe((int) $model->codcontrato));
    }

    public function destroy($id)
    {
        Contrato::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        ContratoService::inativar(Contrato::findOrFail($id));
        return new ContratoResource(ContratoService::detalhe((int) $id));
    }

    public function ativar(Request $request, $id)
    {
        ContratoService::ativar(Contrato::findOrFail($id));
        return new ContratoResource(ContratoService::detalhe((int) $id));
    }
}
