<?php

namespace Mg\Permissao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissaoController extends Controller
{
    public function index(Request $request)
    {
        $qry = PermissaoService::listagemPermissoesPorGrupoUsuario();
        return response()->json($qry, 206);
    }

    public function store(Request $request)
    {
        $permissao = Permissao::firstOrCreate(['permissao' => $request->get('permissao')]);

        GrupoUsuarioPermissao::firstOrCreate([
            'codgrupousuario' => $request->get('codgrupousuario'),
            'codpermissao' => $permissao->codpermissao,
        ]);

        return response()->json(true, 201);
    }

    public function destroy(Request $request, $id)
    {
        if (!$permissao = Permissao::where('permissao', $request->get('permissao'))->first()) {
            return response()->json('', 204);
        }
        if (!$gup = GrupoUsuarioPermissao::where('codgrupousuario', $request->get('codgrupousuario'))
            ->where('codpermissao', $permissao->codpermissao)
            ->first()
        ) {
            return response()->json('', 204);
        }
        $gup->delete();

        return response()->json('', 204);
    }
}
