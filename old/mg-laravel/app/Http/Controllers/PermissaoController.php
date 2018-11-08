<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\PermissaoRepository;

class PermissaoController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\PermissaoRepository';

    public function index(Request $request)
    {
        $this->authorize();
        $qry = app($this->repositoryName)::listagemPermissoesPorGrupoUsuario();
        return response()->json($qry, 200);
    }

    public function store(Request $request)
    {
        $this->authorize();
        PermissaoRepository::adicionaPermissao($request->get('permissao'), $request->get('codgrupousuario'));
        return response()->json(true, 201);
    }

    public function destroy(Request $request, $id)
    {
        $this->authorize();
        PermissaoRepository::removePermissao($request->get('permissao'), $request->get('codgrupousuario'));
        return response()->json('', 204);
    }
}
