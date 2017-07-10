<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\MarcaRepository;

class MarcaController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\MarcaRepository';

    public function index(Request $request)
    {
        $this->authorize();
        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = app($this->repositoryName)::query($filter, $sort, $fields)->with('Imagem');

        $res = $qry->paginate()->appends($request->all());

        foreach ($res as $i => $marca) {
            if (!empty($marca->codimagem)) {
                $res[$i]->imagem->url = $marca->Imagem->url;
            }
        }

        return response()->json($res, 206);
    }

}
