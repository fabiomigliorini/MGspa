<?php

namespace App\Mg\Usuario\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mg\Usuario\Models\Usuario;


class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $model  = Usuario::paginate(20);

        return response()->json($model, 206);
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
            'usuario' => 'required',
        ]);

        $model = new Usuario($request->all());

        $model->create();

        return response()->json($model, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = Usuario::findOrFail($id);

        return response()->json($model, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function details($id)
    {
        $model = Usuario::findOrFail($id);
        $model['pessoa'] = [
            'codpessoa' => 10,
            'pessoa' => 'Teste'
        ];
        /*
        $model['pessoa'] = [
            'codpessoa' => $model->Pessoa->codpessoa ?? null,
            'pessoa' => $model->Pessoa->pessoa ?? null
        ];

        $model['filial'] = [
            'codfilial' => $model->Filial->codfilial,
            'filial' => $model->Filial->filial
        ];

        $grupos = [];
        $permissoes_array = [];
        $permissoes = [];

        foreach ($model->GrupoUsuarioUsuarioS as $grupo) {

            $grupos[$grupo->GrupoUsuario->grupousuario]['grupousuario'] = $grupo->GrupoUsuario->grupousuario;

            if (!isset($grupos[$grupo->GrupoUsuario->grupousuario]['filiais'])) {
                $grupos[$grupo->GrupoUsuario->grupousuario]['filiais'] = [];
            }

            array_push($grupos[$grupo->GrupoUsuario->grupousuario]['filiais'], $grupo->Filial->filial);

            foreach ($grupo->GrupoUsuario->GrupoUsuarioPermissaoS as $permissao) {
                $permissoes_array[] = $permissao->Permissao->permissao;
            }
        }

        foreach ($permissoes_array as $permissao) {
            $key = explode('.', $permissao);
            if (!isset($permissoes[$key[0]])) {
                $permissoes[$key[0]] = array();
            }
            $permissoes[$key[0]][] = $permissao;
        }

        $details['grupos'] = $grupos;
        $details['permissoes'] = $permissoes;
        */

        $model['imagem'] = $model->Imagem->url ?? false;
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
        $request->validate([
            'usuario' => 'required',
        ]);

        $model = Usuario::findOrFail($id);

        $model->update($request->all());

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
}
