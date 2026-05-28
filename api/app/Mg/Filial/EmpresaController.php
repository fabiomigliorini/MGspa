<?php

namespace Mg\Filial;

use Illuminate\Http\Request;
use Mg\MgController;

class EmpresaController extends MgController
{
    public function index(Request $request)
    {
        $empresa = $request->empresa ?? null;
        $codempresa = $request->codempresa ?? null;
        $empresas = EmpresaService::index($empresa, $codempresa);
        return EmpresaResource::collection($empresas);
    }

    public function store(Request $request)
    {
        $reg = EmpresaService::create($request->all());
        return new EmpresaResource($reg);
    }

    public function show($codempresa)
    {
        $reg = Empresa::findOrFail($codempresa);
        return new EmpresaResource($reg);
    }

    public function update(Request $request, $codempresa)
    {
        $reg = Empresa::findOrFail($codempresa);
        $reg = EmpresaService::update($reg, $request->all());
        return new EmpresaResource($reg);
    }

    public function destroy($codempresa)
    {
        $reg = Empresa::findOrFail($codempresa);
        EmpresaService::delete($reg);
        return response()->json(['result' => true], 200);
    }
}
