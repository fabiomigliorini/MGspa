<?php

namespace Mg\Fazenda;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        return response()->json($res, 200);
    }

    public function store(Request $request, $codsafra)
    {
        $request->merge(['codsafra' => $codsafra]);
        $request->validate($this->regras($request));

        $model = new Plantio();
        $model->fill($request->all());
        $model->codsafra = $codsafra;
        $model->save();

        return response()->json($model->fresh(static::WITH), 201);
    }

    public function show(Request $request, $codsafra, $codplantio)
    {
        return response()->json($this->buscar($codsafra, $codplantio, static::WITH), 200);
    }

    public function update(Request $request, $codsafra, $codplantio)
    {
        $model = $this->buscar($codsafra, $codplantio);

        $request->merge(['codsafra' => $codsafra]);
        $request->validate($this->regras($request, $model->codplantio));

        $model->fill($request->all());
        $model->codsafra = $codsafra;
        $model->update();

        return response()->json($model->fresh(static::WITH), 200);
    }

    public function destroy($codsafra, $codplantio)
    {
        $this->buscar($codsafra, $codplantio)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $codsafra, $codplantio)
    {
        return response()->json(PlantioService::inativar($this->buscar($codsafra, $codplantio)), 200);
    }

    public function ativar(Request $request, $codsafra, $codplantio)
    {
        return response()->json(PlantioService::ativar($this->buscar($codsafra, $codplantio)), 200);
    }

    protected function buscar($codsafra, $codplantio, array $with = []): Plantio
    {
        return Plantio::with($with)->where('codsafra', $codsafra)->findOrFail($codplantio);
    }

    protected function regras(Request $request, $codplantio = null): array
    {
        // Talhao+variedade unico por safra+fazenda (entre ativos): permite o
        // mesmo talhao com 2 variedades (2 plantios), barra duplicata real.
        $unico = Rule::unique('tblplantio', 'talhao')->where(
            fn ($q) => $q->where('codsafra', $request->codsafra)
                ->where('codfazenda', $request->codfazenda)
                ->where('codvariedade', $request->codvariedade)
                ->whereNull('inativo')
        );
        if ($codplantio) {
            $unico->ignore($codplantio, 'codplantio');
        }

        return [
            'codsafra' => ['required', 'exists:tblsafra,codsafra'],
            'codfazenda' => ['required', 'exists:tblfazenda,codfazenda'],
            'talhao' => ['required', 'string', 'max:60', $unico],
            'codvariedade' => ['required', 'exists:tblvariedade,codvariedade'],
            'areaplantada' => ['required', 'numeric', 'gt:0'],
            'area' => ['nullable', 'numeric'],
            'geometria' => ['nullable', 'array'],
            'cor' => ['nullable', 'string', 'max:9'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'codtalhao' => ['nullable', 'exists:tbltalhao,codtalhao'],
        ];
    }
}
