<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\ImagemRepository;
use App\Libraries\SlimImageCropper\Slim;

class ImagemController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\ImagemRepository';

    public function store(Request $request)
    {
        $this->authorize();
        $_POST['slim'] = $request->get('slim[]');
        $images = Slim::getImages();
        $data = $request->all();
        $data['imagem'] = $images[0]['output']['data'];

        $model = ImagemRepository::new();
        $model = ImagemRepository::create($model, $data);
        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorize();
        $_POST['slim'] = $request->get('slim[]');
        $images = Slim::getImages();
        $data = $request->all();
        $data['imagem'] = $images[0]['output']['data'];
        $model = ImagemRepository::findOrFail($id);
        ImagemRepository::update($model, $data);
        return response()->json($model, 200);
    }

    public function inactivate(Request $request, $id)
    {
        $data = $request->all();
        $this->authorize();
        $model = ImagemRepository::findOrFail($id);
        $model = ImagemRepository::inactivateImagem($model, $data);
        return response()->json($model, 200);
    }
}
