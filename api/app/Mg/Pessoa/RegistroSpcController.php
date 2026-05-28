<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;

class RegistroSpcController extends MgController
{
    public function index($codpessoa)
    {
        $regs = RegistroSpc::where('codpessoa', $codpessoa)
            ->orderBy('criacao', 'desc')
            ->paginate(10);
        return RegistroSpcResource::collection($regs);
    }

    public function create(Request $request)
    {
        $request->validate([
            'codpessoa' => 'required',
            'inclusao' => 'required',
            'valor' => 'required',
        ]);

        $reg = RegistroSpcService::create($request->all());
        return new RegistroSpcResource($reg);
    }

    public function show(Request $request, $codpessoa, $codregistrospc)
    {
        $reg = RegistroSpc::findOrFail($codregistrospc);
        return new RegistroSpcResource($reg);
    }

    public function update(Request $request, $codpessoa, $codregistrospc)
    {
        $reg = RegistroSpc::findOrFail($codregistrospc);
        $reg = RegistroSpcService::update($reg, $request->all());
        return new RegistroSpcResource($reg);
    }

    public function delete($codpessoa, $codregistrospc)
    {
        $reg = RegistroSpc::findOrFail($codregistrospc);
        $reg = RegistroSpcService::delete($reg);
        return response()->json(['result' => $reg], 200);
    }
}
