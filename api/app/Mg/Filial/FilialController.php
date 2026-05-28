<?php

namespace Mg\Filial;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class FilialController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = FilialService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return FilialResource::collection($res);
    }

    public function show(Request $request, $id)
    {
        $model = Filial::with(['Pessoa', 'Empresa'])->findOrFail($id);
        return new FilialResource($model);
    }

    public function store(Request $request)
    {
        $request->validate([
            'filial' => ['required', 'min:3'],
        ], [
            'filial.required' => 'O campo "Filial" deve ser preenchido!',
            'filial.min' => 'O campo "Filial" deve ter no mínimo 3 caracteres.',
        ]);

        $model = new Filial();
        $model->fill($request->all());
        $model->save();

        $model->load(['Pessoa', 'Empresa']);
        return (new FilialResource($model))->response()->setStatusCode(201);
    }

    public function update(Request $request, $id)
    {
        $model = Filial::findOrFail($id);

        $request->validate([
            'filial' => [
                'required',
                'min:3',
                Rule::unique('tblfilial')->ignore($model->codfilial, 'codfilial'),
            ],
        ], [
            'filial.required' => 'O campo "Filial" deve ser preenchido!',
            'filial.min' => 'O campo "Filial" deve ter no mínimo 3 caracteres.',
            'filial.unique' => 'Esta "Filial" já esta cadastrada',
        ]);

        $model->fill($request->all());
        $model->update();

        $model->load(['Pessoa', 'Empresa']);
        return new FilialResource($model);
    }

    public function destroy($id)
    {
        $model = Filial::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function autor(Request $request, $id)
    {
        $model = Filial::with(['Pessoa'])->findOrFail($id);
        return response()->json([
            'codusuario' => $model->codusuario ?? null,
            'usuario' => $model->usuario ?? null,
            'pessoa' => $model->codpessoa ? ($model->Pessoa->pessoa ?? null) : null,
            'imagem' => null,
        ], 200);
    }
}
