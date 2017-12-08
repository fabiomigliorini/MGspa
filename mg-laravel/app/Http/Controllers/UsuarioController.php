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

    public function autor(Request $request, $id) {
        $usuario = UsuarioRepository::findOrFail($id);
        $res = [
            'codusuario' => $usuario->codusuario,
            'usuario' => $usuario->usuario,
            'pessoa' => null,
            'imagem' => null,
        ];
        if (!empty($usuario->codpessoa)) {
            $res['pessoa'] = $usuario->Pessoa->pessoa;
        }
        if (!empty($usuario->codimagem)) {
            $res['imagem'] = $usuario->Imagem->url;
        }

        return response()->json($res, 200);
    }

    public function impressoras(Request $request) {
        $o = shell_exec("lpstat -d -p");
        $res = explode("\n", $o);
        $printers = [];
        foreach ($res as $r)
        {
            if (strpos($r, "printer") !== FALSE)
            {
                $r = str_replace("printer ", "", $r);
                $r = explode(" ", $r);
                $printers[] = [
                    'label' => $r[0],
                    'value' => $r[0]
                ];
            }
        }

        return response()->json($printers, 200);
    }

    public function grupos(Request $request, $id)
    {
        $this->authorize();
        $model = UsuarioRepository::findOrFail($id);
        $details = UsuarioRepository::grupos($model);
        return response()->json($details, 200);
    }

    public function gruposCreate(Request $request, $id)
    {
        $this->authorize();
        $model = UsuarioRepository::findOrFail($id);
        $grupo_usuario = UsuarioRepository::adicionaGrupo($model, $request->get('codfilial'), $request->get('codgrupousuario'));
        return response()->json($grupo_usuario, 201);
    }

    public function gruposDestroy(Request $request, $id)
    {
        $this->authorize();
        $model = UsuarioRepository::findOrFail($id);
        UsuarioRepository::removeGrupo($model, $request->get('codfilial'), $request->get('codgrupousuario'));
        return response()->json('', 204);
    }

    public function store(Request $request)
    {
        $this->authorize();
        $model = UsuarioRepository::new($request->all());
        if (is_null($model->senha)) {
            unset($model->senha);
        }
        UsuarioRepository::validate($model);
        $model = UsuarioRepository::create($model);
        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorize();
        $model = UsuarioRepository::findOrFail($id);
        $model = UsuarioRepository::fill($model, $request->all());
        $model->senha_confirmacao = $request->get('senha_confirmacao');
        $model->senha_antiga = $request->get('senha_antiga');
        if (is_null($model->senha)) {
            unset($model->senha);
        }
        UsuarioRepository::validate($model);
        unset($model->senha_confirmacao);
        unset($model->senha_antiga);
        $model = UsuarioRepository::update($model);
        return response()->json($model, 200);
    }
}
