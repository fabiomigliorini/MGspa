<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;

class RegistroSpcController extends MgController
{
    public function index($codpessoa)
    {
        $regPessoa = RegistroSpc::where('codpessoa', $codpessoa)
            ->orderBy('criacao', 'desc')->paginate(10);

        return RegistroSpcResource::collection($regPessoa);
    }

    public function create (Request $request)
    {
        $data = $request->all();

        $this->validate($request, [
            'codpessoa' => 'required',
            'inclusao' => 'required',
            'valor' => 'required'
        ]);

        $registro = RegistroSpcService::create($data);
        return new RegistroSpcResource($registro);
    }

    public function show (Request $request, $codpessoa, $codregistrospc)
    {
        $registro = RegistroSpc::findOrFail($codregistrospc);
        return new RegistroSpcResource($registro);
    }

    public function update (Request $request,$codpessoa, $codregistrospc)
    {
        $data = $request->all();
        $registro = RegistroSpc::findOrFail($codregistrospc);
        $registro = RegistroSpcService::update($registro, $data);
        return new RegistroSpcResource($registro);
    }

    public function delete ($codpessoa, $codregistrospc)
    {
        $registro = RegistroSpc::findOrFail($codregistrospc);
        $registro = RegistroSpcService::delete($registro);
        return response()->json([
            'result' => $registro
        ], 200);
    }

}
