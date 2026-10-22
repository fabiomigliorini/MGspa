<?php

namespace Mg\Impressora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

// Cadastro de impressoras (TASK-253): qualquer usuario lista; so' Administrador grava.
class ImpressoraController extends Controller
{
    public function index(Request $request)
    {
        $inativos = $request->filled('inativo')
            ? filter_var($request->inativo, FILTER_VALIDATE_BOOLEAN)
            : null;
        return ImpressoraResource::collection(ImpressoraService::listar($inativos));
    }

    public function show($codimpressora)
    {
        return new ImpressoraResource(Impressora::findOrFail($codimpressora));
    }

    public function store(ImpressoraRequest $request)
    {
        Autorizador::autoriza([]);
        $impressora = DB::transaction(fn () => ImpressoraService::salvar($request->validated()));
        return new ImpressoraResource($impressora);
    }

    public function update(ImpressoraRequest $request, $codimpressora)
    {
        Autorizador::autoriza([]);
        $impressora = Impressora::findOrFail($codimpressora);
        $impressora = DB::transaction(fn () => ImpressoraService::salvar($request->validated(), $impressora));
        return new ImpressoraResource($impressora);
    }

    public function inativar($codimpressora)
    {
        Autorizador::autoriza([]);
        $impressora = DB::transaction(fn () => ImpressoraService::inativar(Impressora::findOrFail($codimpressora)));
        return new ImpressoraResource($impressora);
    }

    public function ativar($codimpressora)
    {
        Autorizador::autoriza([]);
        $impressora = DB::transaction(fn () => ImpressoraService::ativar(Impressora::findOrFail($codimpressora)));
        return new ImpressoraResource($impressora);
    }
}
