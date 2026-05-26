<?php

namespace Mg\Marca;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class MarcaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);

        if (!empty($filter['abccategoria']) && is_string($filter['abccategoria'])) {
            $filter['abccategoria'] = json_decode($filter['abccategoria'], true);
        }

        $qry = MarcaService::pesquisar($filter, $sort, $fields)->with('Imagem');
        $res = $qry->paginate()->appends($request->all());

        foreach ($res as $marca) {
            if (!empty($marca->codimagem) && $marca->Imagem) {
                $marca->imagem->url = $marca->Imagem->url;
            }
        }

        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'marca' => ['required', 'unique:tblmarca', 'min:2'],
        ], [
            'marca.required' => 'O campo "Marca" deve ser preenchido!',
            'marca.unique' => 'Esta " Marca " já esta cadastrado',
            'marca.min' => 'O campo " Marca " deve ter no mínimo 2 caracteres.',
        ]);

        $model = new Marca();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = Marca::findOrFail($id);
        return response()->json($model, 200);
    }

    public function detalhes($id)
    {
        $model = MarcaService::detalhes((int) $id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = Marca::findOrFail($id);

        $request->validate([
            'marca' => [
                'required',
                Rule::unique('tblmarca')->ignore($model->codmarca, 'codmarca'),
                'min:2',
            ],
        ], [
            'marca.required' => 'O campo "Marca" deve ser preenchido!',
            'marca.unique' => 'Esta "Marca" já esta cadastrada',
            'marca.min' => 'O campo "Marca" deve ter no mínimo 2 caracteres.',
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 201);
    }

    public function destroy($id)
    {
        $model = Marca::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function ativar(Request $request, $id)
    {
        $model = Marca::findOrFail($id);
        $model = MarcaService::ativar($model);
        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id)
    {
        $model = Marca::findOrFail($id);
        $model = MarcaService::inativar($model);
        return response()->json($model, 200);
    }

    public function autocompletar(Request $request)
    {
        $res = MarcaService::autocompletar($request->all());
        return response()->json($res, 200);
    }
}
