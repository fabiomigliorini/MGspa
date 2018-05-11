<?php

namespace Mg\Permissao;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

use Mg\Usuario\GrupoUsuario;

class PermissaoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $qry = PermissaoRepository::listagemPermissoesPorGrupoUsuario();
        return response()->json($qry, 206);
    }

    public function store(Request $request)
    {
        if (!$permissao = Permissao::where('permissao', $request->get('permissao'))->first()) {
            $permissao = new Permissao([
                'permissao' => $request->get('permissao')
            ]);
            $permissao->save();
        }

        if (!GrupoUsuarioPermissao::where('codgrupousuario', $request->get('codgrupousuario'))->where('codpermissao', $permissao->codpermissao)->first()) {
            $gup = new GrupoUsuarioPermissao();
            $gup->fill([
                'codgrupousuario' => $request->get('codgrupousuario'),
                'codpermissao' => $permissao->codpermissao,
            ]);

            if (!$gup->save()) {
                return false;
            }
        }

        return response()->json(true, 201);
    }

    public function destroy(Request $request, $id)
    {
        if (!$permissao = Permissao::where('permissao', $request->get('permissao'))->first()) {
            return true;
        }
        if (!$gup = GrupoUsuarioPermissao::where('codgrupousuario', $request->get('codgrupousuario'))->where('codpermissao', $permissao->codpermissao)->first()) {
            return true;
        }
        $gup->delete();

        return response()->json('', 204);
    }
}
