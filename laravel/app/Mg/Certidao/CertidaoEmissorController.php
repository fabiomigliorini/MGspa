<?php

namespace Mg\Certidao;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\MgService;

class CertidaoEmissorController extends MgController
{

    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $qry = CertidaoEmissorService::pesquisar($filter, $sort, $fields);
        $regs = $qry->orderBy('certidaoemissor', 'asc')->paginate()->appends($request->all());
        return CertidaoEmissorResource::collection($regs);
    }

    public function store(CertidaoEmissorStoreRequest $request)
    {
        $reg = CertidaoEmissor::create($request->validated());
        return (new CertidaoEmissorResource($reg))->response()->setStatusCode(201);
    }

    public function update(CertidaoEmissorUpdateRequest $request, $codcertidaoemissor)
    {
        $reg = CertidaoEmissor::findOrFail($codcertidaoemissor);
        $reg->update($request->validated());
        return new CertidaoEmissorResource($reg);
    }

    public function destroy($codcertidaoemissor)
    {
        $reg = CertidaoEmissor::findOrFail($codcertidaoemissor);
        $reg->delete();
        return response()->json(null, 204);
    }

    public function inativar($codcertidaoemissor)
    {
        $reg = CertidaoEmissor::findOrFail($codcertidaoemissor);
        MgService::inativar($reg);
        return new CertidaoEmissorResource($reg);
    }

    public function ativar($codcertidaoemissor)
    {
        $reg = CertidaoEmissor::findOrFail($codcertidaoemissor);
        MgService::ativar($reg);
        return new CertidaoEmissorResource($reg);
    }

}
