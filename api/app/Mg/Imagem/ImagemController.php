<?php

namespace Mg\Imagem;

use Illuminate\Http\Request;
use Mg\MgController;

class ImagemController extends MgController
{
    public function store(Request $request)
    {
        $images = SlimAdapter::getImages('slim');
        if (empty($images)) {
            abort(422, 'Nenhuma imagem recebida no campo "slim".');
        }
        $data = $request->all();
        $data['imagem'] = $images[0]['output']['data'] ?? $images[0]['output']['image'] ?? null;

        $model = new Imagem();
        $model = ImagemService::criar($model, $data);
        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {
        $images = SlimAdapter::getImages('slim');
        if (empty($images)) {
            abort(422, 'Nenhuma imagem recebida no campo "slim".');
        }
        $data = $request->all();
        $data['imagem'] = $images[0]['output']['data'] ?? $images[0]['output']['image'] ?? null;

        $model = Imagem::findOrFail($id);
        $model = ImagemService::atualizar($model, $data);
        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id)
    {
        $data = $request->all();
        $model = Imagem::findOrFail($id);
        $model = ImagemService::inativarImagem($model, $data);
        return response()->json($model, 200);
    }
}
