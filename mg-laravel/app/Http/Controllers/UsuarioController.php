<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\UsuarioRepository;

class UsuarioController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\UsuarioRepository';
    public function index(Request $request)
    {
        $this->authorize();
        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = app($this->repositoryName)::query($filter, $sort, $fields)->with('Imagem');
        $res = $qry->paginate()->appends($request->all());

        foreach ($res as $i => $usuario) {
            if (!empty($usuario->codimagem)) {
                $res[$i]->imagem->url = $usuario->Imagem->url;
            }

            $grupos = [];
            foreach ($usuario->GrupoUsuarioUsuarioS as $grupo) {
                $grupos[] = [
                    'grupousuario' => $grupo->GrupoUsuario->grupousuario,
                    'filial' => $grupo->Filial->filial
                ];
            }

            $res[$i]->grupos = $grupos;
        }

        return response()->json($res, 206);
    }
}
