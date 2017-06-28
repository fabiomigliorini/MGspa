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
        /*
        PermissaoRepository::adicionaPermissao('natureza-operacao.index', 1);
        PermissaoRepository::adicionaPermissao('natureza-operacao.show', 1);
        PermissaoRepository::adicionaPermissao('natureza-operacao.store', 1);
        PermissaoRepository::adicionaPermissao('natureza-operacao.update', 1);
        PermissaoRepository::adicionaPermissao('natureza-operacao.delete', 1);
        PermissaoRepository::adicionaPermissao('natureza-operacao.activate', 1);
        PermissaoRepository::adicionaPermissao('natureza-operacao.inactivate', 1);
        dd(Route::currentRouteName());
        */
        if (!PermissaoRepository::authorize($codfilial, $codusuario, $rota)) {
            abort(403);
        }
        return true;
    }
}
