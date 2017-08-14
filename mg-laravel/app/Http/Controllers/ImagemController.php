<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\ImagemRepository;

class ImagemController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\ImagemRepository';

    public function store(Request $request)
    {
        $this->authorize();
        $data = $request->all();
        $model = ImagemRepository::new();
        // ImagemRepository::validate($data);
        $model = ImagemRepository::create($model, $data);
        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorize();
        $data = $request->all();
        $model = ImagemRepository::findOrFail($id);
        // ImagemRepository::validate($model);
        ImagemRepository::update($model, $data);
        return response()->json($model, 200);
    }

}
