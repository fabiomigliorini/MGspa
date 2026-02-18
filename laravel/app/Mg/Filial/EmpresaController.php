<?php

namespace Mg\Filial;

use Illuminate\Http\Request;
use Mg\MgController;

class EmpresaController extends MgController
{
    public function index(Request $request)
    {
        $empresa = $request->empresa ?? null;
        $empresas = EmpresaService::index($empresa);

        $codempresa = $request->codempresa ?? null;
        $empresas = EmpresaService::index($empresa, $codempresa);
        return EmpresaResource::collection($empresas);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $empresa = EmpresaService::create($data);
        return new EmpresaResource($empresa);
    }

    public function show($codempresa)
    {
        $empresa = Empresa::findOrFail($codempresa);
        return new EmpresaResource($empresa);
    }

    public function update(Request $request, $codempresa)
    {
        $data = $request->all();
        $empresa = Empresa::findOrFail($codempresa);
        $empresa = EmpresaService::update($empresa, $data);
        return new EmpresaResource($empresa);
    }

    public function destroy($codempresa)
    {
        $empresa = Empresa::findOrFail($codempresa);
        EmpresaService::delete($empresa);
        return response()->json(['result' => true], 200);
    }
}
