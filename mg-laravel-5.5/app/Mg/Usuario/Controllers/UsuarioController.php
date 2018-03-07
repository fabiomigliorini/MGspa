<?php

namespace App\Mg\Usuario\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mg\Usuario\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = Usuario::search($filter, $sort, $fields)->with('Imagem');
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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function resource(Request $request)
    {

        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = Usuario::search($filter, $sort, $fields)->with('Imagem');
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

        return \App\Mg\Usuario\Resources\UsuarioResource::collection($res);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'usuario' => [
                'required',
                'unique:tblusuario',
                'min:2',
            ],
            'senha' => [
                'required',
                'min:6'
            ],
            'impressoramatricial' => [
                'required'
            ],
            'impressoratermica' => [
                'required'
            ]
        ], [
            'usuario.required' => 'O campo "Usuario" deve ser preenchido!',
            'usuario.unique' => 'Este "Usuario" já esta cadastrado',
            'usuario.min' => 'O campo "Usuario" deve ter no mínimo 2 caracteres.',
            'senha.min' => 'O campo "Senha" deve ter no mínimo 6 caracteres.',
            'senha.required' => 'O campo "Senha" deve ser preenchido!',
            'impressoratermica.required' => 'O campo "Impressora Termica" deve ser preenchido!',
            'impressoramatricial.required' => 'O campo "Impressora Matricial" deve ser preenchido!',
        ]);

        $model = new Usuario($request->all());
        $model->senha = bcrypt($model->senha);
        $model->create();

        return response()->json($model, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $model = Usuario::findOrFail($id, $request->get('fields'));

        return response()->json($model, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function details ($id)
    {
        $model = \App\Mg\Usuario\Repositories\UsuarioRepository::details($id);
        // $model = Usuario::findOrFail($id);
        // $model['pessoa'] = [
        //     'codpessoa' => $model->Pessoa->codpessoa ?? null,
        //     'pessoa' => $model->Pessoa->pessoa ?? null
        // ];
        //
        // $model['filial'] = [
        //     'codfilial' => $model->Filial->codfilial,
        //     'filial' => $model->Filial->filial
        // ];
        //
        // $grupos = [];
        // $permissoes_array = [];
        // $permissoes = [];
        //
        // foreach ($model->GrupoUsuarioUsuarioS as $grupo) {
        //
        //     $grupos[$grupo->GrupoUsuario->grupousuario]['grupousuario'] = $grupo->GrupoUsuario->grupousuario;
        //
        //     if (!isset($grupos[$grupo->GrupoUsuario->grupousuario]['filiais'])) {
        //         $grupos[$grupo->GrupoUsuario->grupousuario]['filiais'] = [];
        //     }
        //
        //     array_push($grupos[$grupo->GrupoUsuario->grupousuario]['filiais'], $grupo->Filial->filial);
        //
        //     foreach ($grupo->GrupoUsuario->GrupoUsuarioPermissaoS as $permissao) {
        //         $permissoes_array[] = $permissao->Permissao->permissao;
        //     }
        // }
        //
        // foreach ($permissoes_array as $permissao) {
        //     $key = explode('.', $permissao);
        //     if (!isset($permissoes[$key[0]])) {
        //         $permissoes[$key[0]] = array();
        //     }
        //     $permissoes[$key[0]][] = $permissao;
        // }
        //
        // $details['grupos'] = $grupos;
        // $details['permissoes'] = $permissoes;
        //
        // $model['imagem'] = $model->Imagem->url ?? false;
        return response()->json($model, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);

        \Validator::extend('senhaAntiga', function ($attribute, $value, $parameters, $validator) {
            if (!$value) {
                return true;
            }

            return \Hash::check($value, \Auth::user()->senha);
        });

        \Validator::extend('senhaConfirmacao', function ($attribute, $value, $parameters, $validator) {

            if ($value == $parameters[0]) {
                return true;
            }

            return false;
        });

        $request->validate([
            'usuario' => [
                'required',
                Rule::unique('tblusuario')->ignore($model->codusuario, 'codusuario'),
                'min:2',
            ],
            'senha' => [
                'senha_confirmacao:'.$request->get('senha_confirmacao'),
                'min:6'
            ],
            'senha_antiga' => [
                'senha_antiga'
            ],
            'impressoramatricial' => [
                'required'
            ],
            'impressoratermica' => [
                'required'
            ]
        ], [
            'usuario.required' => 'O campo "Usuário" deve ser preenchido!',
            'usuario.unique' => 'Este "Usuário" já esta cadastrado',
            'usuario.min' => 'O campo "Usuário" deve ter no mínimo 2 caracteres.',
            'senha.min' => 'O campo "Senha" deve ter no mínimo 6 caracteres.',
            'senha.required' => 'O campo "Senha" deve ser preenchido!',
            'senha.senha_confirmacao' => 'Confirmação de senha não confere',
            'impressoratermica.required' => 'O campo "Impressora Termica" deve ser preenchido!',
            'impressoramatricial.required' => 'O campo "Impressora Matricial" deve ser preenchido!',
            'senha_antiga.senha_antiga' => 'Senha antiga não confere',
        ]);

        $model->fill($request->all());

        if ($request->get('senha')) {
            $model->senha = bcrypt($model->senha);
        }

        $model->update();

        return response()->json($model, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $model = Usuario::findOrFail($id);
        $model->delete();
    }
    public function author(Request $request, $id) {
        $model = Usuario::findOrFail($id);
        $res = [
            'codusuario' => $model->codusuario,
            'usuario' => $model->usuario,
            'pessoa' => null,
            'imagem' => null,
        ];
        if (!empty($model->codpessoa)) {
            $res['pessoa'] = $model->Pessoa->pessoa;
        }
        if (!empty($model->codimagem)) {
            $res['imagem'] = $model->Imagem->url;
        }

        return response()->json($res, 200);
    }

    public function activate(Request $request, $id) {
        $model = Usuario::findOrFail($id);
        $model->activate();
        return response()->json($model, 200);
    }

    public function inactivate(Request $request, $id) {
        $model = Usuario::findOrFail($id);
        $model->inactivate();
        return response()->json($model, 200);
    }

    public function groups(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);

        $grupos_usuario = [];
        foreach ($model->GrupoUsuarioUsuarioS as $guu) {
            $grupos_usuario[$guu->codgrupousuario][$guu->codfilial] = $guu->codgrupousuariousuario;
        }

        return response()->json($grupos_usuario, 200);
    }

    public function groupsCreate(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        $grupo_usuario = false;
        if (!$model->GrupoUsuarioUsuarioS()->where('codgrupousuario', $request->get('codgrupousuario'))->where('codfilial', $request->get('codfilial'))->first()) {
            $grupo_usuario = new \App\Mg\Usuario\Models\GrupoUsuarioUsuario([
                'codusuario'        => $model->codusuario,
                'codgrupousuario'   => $request->get('codgrupousuario'),
                'codfilial'         => $request->get('codfilial')
            ]);

            $grupo_usuario->save();
        }

        return response()->json($grupo_usuario, 201);
    }

    public function groupsDestroy(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        $model->GrupoUsuarioUsuarioS()->where('codgrupousuario', $request->get('codgrupousuario'))->where('codfilial', $request->get('codfilial'))->delete();
        return response()->json($model, 204);
    }

    public function parseSearchRequest(Request $request)
    {
        $req = $request->all();

        $sort = $request->sort;
        if (!empty($sort)) {
            $sort = explode(',', $sort);
        }

        $fields = $request->fields;
        if (!empty($fields)) {
            $fields = explode(',', $fields);
        }

        $filter = $request->all();

        unset($filter['fields']);
        unset($filter['sort']);
        unset($filter['page']);

        return [
            $filter,
            $sort,
            $fields,
        ];
    }

}