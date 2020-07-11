<?php

namespace Mg\Etiqueta;

// use App\Http\Requests;
use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Produto\ProdutoService;

class EtiquetaController extends MgController
{

    public function barras(Request $request)
    {
        $request->validate([
            'barras' => ['required', 'string'],
        ]);
        if (!$pb = ProdutoService::buscaPorBarras($request->barras)) {
            abort(404, 'Produto Não Localizado!');
        }
        return new EtiquetaResource($pb);
    }

}
