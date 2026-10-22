<?php

namespace Mg\Negocio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Entregas do negocio (TASK-55, doc-6): listas por loja e o registro de cada tentativa.
class NegocioEntregaController extends Controller
{
    public function pendentes(Request $request, string $lado)
    {
        $request->validate(['codestoquelocal' => 'required|integer']);
        return response()->json(NegocioEntregaService::pendentes(
            (int) $request->codestoquelocal,
            $lado === 'a-receber' ? 'receber' : 'fazer'
        ));
    }

    public function index($codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        return NegocioEntregaResource::collection(NegocioEntregaService::entregas($negocio))
            ->additional(['entrega' => (bool) $negocio->entrega, 'entregue' => $negocio->entregue]);
    }

    public function store(NegocioEntregaRequest $request, $codnegocio)
    {
        $entrega = DB::transaction(function () use ($request, $codnegocio) {
            $negocio = Negocio::lockForUpdate()->findOrFail($codnegocio);
            return NegocioEntregaService::registrar($negocio, $request->validated());
        });
        return new NegocioEntregaResource($entrega->load('UsuarioCriacao'));
    }

    public function inativar($codnegocioentrega)
    {
        $entrega = DB::transaction(function () use ($codnegocioentrega) {
            $entrega = NegocioEntrega::lockForUpdate()->findOrFail($codnegocioentrega);
            return NegocioEntregaService::inativar($entrega);
        });
        return new NegocioEntregaResource($entrega->load('UsuarioCriacao'));
    }
}
