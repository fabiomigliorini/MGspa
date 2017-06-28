<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

use Route;

use App\Repositories\PermissaoRepository;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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

    public function authorize ($codfilial = null, $codusuario = null, $rota = null)
    {
        // PermissaoRepository::adicionaPermissao('usuario.index', 1);
        // PermissaoRepository::adicionaPermissao('usuario.show', 1);
        // PermissaoRepository::adicionaPermissao('usuario.store', 1);
        //PermissaoRepository::adicionaPermissao('usuario.update', 1);
        // PermissaoRepository::adicionaPermissao('usuario.destroy', 1);
        // PermissaoRepository::adicionaPermissao('usuario.inactivate', 1);
        // PermissaoRepository::adicionaPermissao('usuario.activate', 1);

        if (!PermissaoRepository::authorize($codfilial, $codusuario, $rota)) {
            abort(403);
        }
        return true;
    }
}
