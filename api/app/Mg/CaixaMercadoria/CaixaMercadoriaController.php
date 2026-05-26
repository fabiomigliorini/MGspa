<?php

namespace Mg\CaixaMercadoria;

use Illuminate\Http\Request;
use Mg\MgController;

/**
 * Stub controller — porta literal do legacy. Não faz nada útil; existe
 * apenas porque a rota `caixa-mercadoria` é referenciada pelo frontend.
 * Mantido por paridade até decisão sobre o destino do recurso.
 */
class CaixaMercadoriaController extends MgController
{
    public function index(Request $request)
    {
        return response()->json($request->all(), 206);
    }

    public function show(Request $request, $id)
    {
        return response()->json($request->all(), 200);
    }

    public function store(Request $request)
    {
        return response()->json($request->all(), 201);
    }

    public function update(Request $request, $id)
    {
        return response()->json($request->all(), 201);
    }

    public function destroy(Request $request, $id)
    {
        return response()->noContent();
    }
}
