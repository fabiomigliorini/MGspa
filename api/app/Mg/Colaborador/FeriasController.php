<?php

namespace Mg\Colaborador;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class FeriasController extends MgController
{

    public function create(Request $request, $codcolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $data = $request->all();
        $criarFerias = FeriasService::create($data);
        return new FeriasResource($criarFerias);
    }

    public function show(Request $request, $codcolaborador, $codferias)
    {
        $ferias = Ferias::findOrFail($codferias);
        return new FeriasResource($ferias);
    }

    public function update(Request $request, $codcolaborador, $codferias)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $data = $request->all();
        $ferias = Ferias::findOrFail($codferias);
        $ferias = FeriasService::update($ferias, $data);

        return new FeriasResource($ferias);
    }

    public function delete(Request $request, $codcolaborador, $codferias)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $ferias = Ferias::findOrFail($codferias);
        $ferias = FeriasService::delete($ferias);

        return response()->json([
            'result' => true
        ], 200);
    }

    public function programacaoFerias(Request $request, $ano)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $ferias = FeriasService::programacaoFerias($ano);
        return response()->json(
            $ferias,
            200
        );
    }

    public function AtualizaTodasFerias(Request $request)
    {
        $arr = array_values($request->all());

        foreach ($arr as $key => $ferias) {
            foreach ($ferias as $key => $value) {
                unset($value->diagozoinicio);
                unset($value->diagozofim);
                unset($value->range);
                $ferias = Ferias::findOrFail($value['codferias']);
                $atualizaTodasFerias =  FeriasService::update($ferias, $value);
            }
        }

        return response()->json($atualizaTodasFerias, 200);
    }
}
