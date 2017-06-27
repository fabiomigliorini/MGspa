<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Repositories\PermissaoRepository;

class PermissaoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Permissao
        PermissaoRepository::authorize('listing');

        // list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        // $qry = PermissaoRepository::query($filter, $sort, $fields);
        // return response()->json($qry->paginate()->appends($request->all()), 206);

        return response()->json([
            'classes' =>  PermissaoRepository::permissaoClasses(),
            'permissoes' => PermissaoRepository::permissaoGrupos()
        ], 206);
        /*
        // Listagem dos Grupos
        $grupos = GrupoUsuario::ativo()->orderBy('grupousuario')->get();

        // Listagem das Permissoes dos Grupos
        $grupopermissoes = GrupoUsuarioPermissao::select('codgrupousuariopermissao', 'codgrupousuario', 'codpermissao')->get();
        $permissoes = [];
        foreach ($grupopermissoes as $grupopermissao) {
            $permissoes[$grupopermissao->codgrupousuario][$grupopermissao->codpermissao] = $grupopermissao->codgrupousuariopermissao;
        }

        return view('permissao.index', ['bc'=>$this->bc, 'classes'=>$classes, 'grupos'=>$grupos, 'permissoes'=>$permissoes]);
        */
    }

    public function store(Request $request)
    {
        /*
        // Permissao
        $this->repository->authorize('create');

        // Monta a chave da permissao
        if ($request->classe == 'OBSOLETAS') {
            $chave = $request->metodo;
        } else {
            $chave = "{$request->classe}.{$request->metodo}";
        }

        // Se nao Existe Permissao, cria
        if (!$permissao = Permissao::where('permissao', $chave)->first()) {
            $permissao = Permissao::create(['permissao' => $chave]);
        }

        // Associa a permissao com o grupo de usuario
        if (!$grupo_permissao = GrupoUsuarioPermissao::where('codgrupousuario', $request->codgrupousuario)->where('codpermissao', $permissao->codpermissao)->first()) {
            $grupo_permissao = GrupoUsuarioPermissao::create(['codgrupousuario' => $request->codgrupousuario, 'codpermissao'=>$permissao->codpermissao]);
        }

        //retorna
        return [
            'OK' => $grupo_permissao->codgrupousuariopermissao,
            'grupousuario' => $grupo_permissao->GrupoUsuario->grupousuario,
            'permissao' => $chave,
        ];
        */
    }

    public function destroyPermissao(Request $request)
    {
        /*
        // Permissao
        $this->repository->authorize('delete');

        // monta a chave da permissao
        if ($request->classe == 'OBSOLETAS') {
            $chave = $request->metodo;
        } else {
            $chave = "{$request->classe}.{$request->metodo}";
        }

        // Se nao existe a permissao, aborta
        if (!$permissao = Permissao::where('permissao', $chave)->first()) {
            abort(404);
        }

        // Exclui registros
        $excluidos = GrupoUsuarioPermissao::where('codgrupousuario', $request->codgrupousuario)->where('codpermissao', $permissao->codpermissao)->delete();

        //retorna
        return [
            'OK' => $excluidos,
            'grupousuario' => GrupoUsuario::findOrFail($request->codgrupousuario)->grupousuario,
            'permissao' => $chave,
        ];
        */
    }
}
