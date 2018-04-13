<?php

namespace Imagem;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Mg\MgController;

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
        $model = ImagemRepository::create($model, $data);
        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {
        $_POST['slim'] = $request->get('slim[]');
        $images = Slim::getImages();
        $data = $request->all();
        $data['imagem'] = $images[0]['output']['data'];
        $model = Imagem::findOrFail($id);
        $model = ImagemRepository::update($model, $data);
        return response()->json($model, 200);
    }

    public function inactivate(Request $request, $id)
    {
        $data = $request->all();
        $model = Imagem::findOrFail($id);
        $model = ImagemRepository::inactivateImagem($model, $data);
        return response()->json($model, 200);
    }
}
