<?php

namespace Mg\Usuario;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Mg\MgController;

class UsuarioController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = UsuarioService::pesquisar($filter, $sort, $fields)
            ->with(['Pessoa:codpessoa,pessoa', 'GrupoUsuarioUsuarioS.GrupoUsuario:codgrupousuario,grupousuario']);
        $res = $qry->orderBy('usuario', 'asc')->paginate()->appends($request->all());

        return UsuarioListResource::collection($res);
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario' => ['required', 'unique:tblusuario', 'min:2'],
            'impressoramatricial' => ['required'],
            'impressoratermica' => ['required'],
        ], [
            'usuario.required' => 'O campo "Usuario" deve ser preenchido!',
            'usuario.unique' => 'Este "Usuario" já esta cadastrado',
            'usuario.min' => 'O campo "Usuario" deve ter no mínimo 2 caracteres.',
            'impressoratermica.required' => 'O campo "Impressora Termica" deve ser preenchido!',
            'impressoramatricial.required' => 'O campo "Impressora Matricial" deve ser preenchido!',
        ]);

        $model = new Usuario();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        return response()->json($model, 200);
    }

    public function detalhes($id)
    {
        $model = UsuarioService::detalhes($id);
        return new UsuarioResource($model);
    }

    public function update(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);

        $request->validate([
            'usuario' => [
                'required',
                Rule::unique('tblusuario')->ignore($model->codusuario, 'codusuario'),
                'min:2',
            ],
        ], [
            'usuario.required' => 'O campo "Usuário" deve ser preenchido!',
            'usuario.unique' => 'Este "Usuário" já esta cadastrado',
            'usuario.min' => 'O campo "Usuário" deve ter no mínimo 2 caracteres.',
        ]);

        $model->fill($request->all());
        $model->update();

        return new UsuarioResource($model);
    }

    public function destroy($id)
    {
        $model = Usuario::findOrFail($id);
        $model->delete();

        return response()->json(['result' => $model], 200);
    }

    public function autor(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        $res = [
            'codusuario' => $model->codusuario,
            'usuario' => $model->usuario,
            'pessoa' => null,
            'imagem' => null,
        ];
        if (!empty($model->codpessoa)) {
            $res['pessoa'] = $model->Pessoa->pessoa ?? null;
        }
        if (!empty($model->codimagem)) {
            $res['imagem'] = $model->Imagem->url ?? null;
        }

        return response()->json($res, 200);
    }

    public function ativar(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        $model = UsuarioService::ativar($model);

        return new UsuarioResource($model);
    }

    public function inativar(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        $model = UsuarioService::inativar($model);

        return new UsuarioResource($model);
    }

    public function grupos(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);

        $grupos_usuario = [];
        foreach ($model->GrupoUsuarioUsuarioS as $guu) {
            $grupos_usuario[$guu->codgrupousuario][$guu->codfilial] = $guu->codgrupousuariousuario;
        }

        return response()->json($grupos_usuario, 200);
    }

    public function gruposAdicionar(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        $grupo_usuario = false;
        if (!$model->GrupoUsuarioUsuarioS()
            ->where('codgrupousuario', $request->get('codgrupousuario'))
            ->where('codfilial', $request->get('codfilial'))
            ->first()) {
            $grupo_usuario = GrupoUsuarioUsuario::create([
                'codusuario' => $model->codusuario,
                'codgrupousuario' => $request->get('codgrupousuario'),
                'codfilial' => $request->get('codfilial'),
            ]);
        }

        return response()->json($grupo_usuario, 201);
    }

    public function gruposRemover(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        $model->GrupoUsuarioUsuarioS()
            ->where('codgrupousuario', $request->get('codgrupousuario'))
            ->where('codfilial', $request->get('codfilial'))
            ->delete();
        return response()->json($model, 204);
    }

    public function permissoesUsuarios(Request $request)
    {
        $usuario = Auth::user();
        $token = $request->user()->token();
        $expiresAt = $token?->expires_at;
        $expiresIn = $expiresAt ? max(0, Carbon::now()->diffInSeconds($expiresAt, false)) : null;

        return (new UsuarioResource($usuario))->additional([
            'meta' => [
                'expires_in' => $expiresIn,
                'expires_at' => $expiresAt?->toIso8601String(),
            ],
        ]);
    }

    public function gruposAdicionarERemover(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario = UsuarioService::updateUsuario($usuario, $request->all());
        return new UsuarioResource($usuario);
    }

    public function novoUsuario(Request $request)
    {
        $request->validate([
            'usuario' => ['required', 'unique:tblusuario', 'min:2'],
            'senha' => ['required', 'min:6'],
        ]);
        $usuario = UsuarioService::create($request->all());
        return new UsuarioResource($usuario);
    }

    /**
     * O próprio usuário autenticado altera a sua senha (exige a senha antiga).
     */
    public function alterarMinhaSenha(Request $request)
    {
        $request->validate([
            'senha_antiga' => ['required'],
            'senha' => ['required', 'min:6', 'same:senha_confirmacao'],
            'senha_confirmacao' => ['required'],
        ], [
            'senha_antiga.required' => 'Digite a senha antiga.',
            'senha.required' => 'Digite a nova senha.',
            'senha.min' => 'A nova senha deve ter no mínimo 6 caracteres.',
            'senha.same' => 'A confirmação de senha não confere.',
            'senha_confirmacao.required' => 'Confirme a nova senha.',
        ]);

        $usuario = Auth::user();

        if (!Hash::check($request->senha_antiga, $usuario->senha)) {
            throw ValidationException::withMessages([
                'senha_antiga' => ['Senha antiga não confere.'],
            ]);
        }

        UsuarioService::definirSenha($usuario, $request->senha);

        return response()->json(['message' => 'Senha alterada com sucesso!'], 200);
    }

    /**
     * Um administrador altera a senha de outro usuário (não exige a senha antiga).
     */
    public function alterarSenha(Request $request, $id)
    {
        Autorizador::autoriza([]);

        $request->validate([
            'senha' => ['required', 'min:6', 'same:senha_confirmacao'],
            'senha_confirmacao' => ['required'],
        ], [
            'senha.required' => 'Digite a nova senha.',
            'senha.min' => 'A nova senha deve ter no mínimo 6 caracteres.',
            'senha.same' => 'A confirmação de senha não confere.',
            'senha_confirmacao.required' => 'Confirme a nova senha.',
        ]);

        $usuario = Usuario::findOrFail($id);
        UsuarioService::definirSenha($usuario, $request->senha);

        return response()->json(['message' => 'Senha alterada com sucesso!'], 200);
    }
}
