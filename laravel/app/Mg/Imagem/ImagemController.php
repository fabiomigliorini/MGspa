<?php

namespace Mg\Imagem;

use Illuminate\Http\Request;

use App\Http\Requests;
use Mg\MgController;

use App\Libraries\SlimImageCropper\Slim;

class ImagemController extends MgController
{

    public function store(Request $request)
    {
        $_POST['slim'] = $request->get('slim[]');
        $images = Slim::getImages();
        $data = $request->all();
        $data['imagem'] = $images[0]['output']['data'];

        $model = new Imagem();
        $model = ImagemService::criar($model, $data);
        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {
        $_POST['slim'] = $request->get('slim[]');
        $images = Slim::getImages();
        $data = $request->all();
        $data['imagem'] = $images[0]['output']['data'];
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
