<?php

namespace Mg\Safra;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class SafraController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = SafraService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'safra' => ['required', 'min:2'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'anoplantio' => [
                'required', 'integer', 'min:2000', 'max:2100',
                Rule::unique('tblsafra', 'anoplantio')->where('codcultura', $request->codcultura),
            ],
            'anocolheita' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], [
            'anoplantio.unique' => 'Já existe uma safra dessa cultura para esse ano de plantio.',
        ]);

        $model = new Safra();
        $model->fill($request->all());
        $model->save();

        return response()->json($model->fresh('Cultura'), 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json(Safra::with('Cultura')->findOrFail($id), 200);
    }

    public function update(Request $request, $id)
    {
        $model = Safra::findOrFail($id);

        $request->validate([
            'safra' => ['required', 'min:2'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'anoplantio' => [
                'required', 'integer', 'min:2000', 'max:2100',
                Rule::unique('tblsafra', 'anoplantio')
                    ->ignore($id, 'codsafra')
                    ->where('codcultura', $request->codcultura),
            ],
            'anocolheita' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], [
            'anoplantio.unique' => 'Já existe uma safra dessa cultura para esse ano de plantio.',
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model->fresh('Cultura'), 200);
    }

    public function destroy($id)
    {
        Safra::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(SafraService::inativar(Safra::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(SafraService::ativar(Safra::findOrFail($id)), 200);
    }
}
