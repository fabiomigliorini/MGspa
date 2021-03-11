<?php

namespace Mg\Veiculo;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use Mg\NotaFiscal\NotaFiscal;

class VeiculoConjuntoController
{
    public function index (Request $request)
    {
        $conjuntos = VeiculoConjunto::orderBy('veiculoconjunto')->get();
        return VeiculoConjuntoResource::collection($conjuntos);
    }

    public function show (Request $request, $id)
    {
        $conjunto = VeiculoConjunto::findOrFail($id);
        return new VeiculoConjuntoResource($conjunto);
    }

    public function update (Request $request, $id)
    {

        $request->validate([
            'veiculoconjunto' => [
                'required',
                Rule::unique('tblveiculoconjunto')->ignore($id, 'codveiculoconjunto'),
                'min:5',
                'max:50',
            ],
            'veiculos' => ['required', 'array', 'min:2'],
            'veiculos.*.codveiculo' => ['required'],
        ]);

        $conjunto = VeiculoConjunto::findOrFail($id);
        $conjunto->fill($request->all());
        $conjunto->save();
        $cods = [];
        foreach ($request->veiculos as $veiculo) {
            $cods[] = $veiculo['codveiculo'];
            $vcv = VeiculoConjuntoVeiculo::firstOrCreate([
                'codveiculo' => $veiculo['codveiculo'],
                'codveiculoconjunto' => $conjunto->codveiculoconjunto,
            ]);
        }
        VeiculoConjuntoVeiculo::where('codveiculoconjunto', $conjunto->codveiculoconjunto)->whereNotIn('codveiculo', $cods)->delete();
        return new VeiculoConjuntoResource($conjunto);
    }

    public function inativar (Request $request, $id)
    {
        $conjunto = VeiculoConjunto::findOrFail($id);
        $conjunto->update([
            'inativo' => Carbon::now()
        ]);
        return new VeiculoConjuntoResource($conjunto);
    }

    public function ativar (Request $request, $id)
    {
        $conjunto = VeiculoConjunto::findOrFail($id);
        $ret = $conjunto->update([
            'inativo' => null
        ]);
        return new VeiculoConjuntoResource($conjunto);
    }

    public function delete (Request $request, $id)
    {
        $conjunto = VeiculoConjunto::findOrFail($id);
        $conjunto->delete();
    }

    public function store (Request $request)
    {
        $request->validate([
            'veiculoconjunto' => ['required', 'unique:tblveiculoconjunto', 'min:5', 'max:50'],
            'veiculos' => ['required', 'array', 'min:2'],
            'veiculos.*.codveiculo' => ['required'],
        ]);
        $conjunto = VeiculoConjunto::create($request->all());
        foreach ($request->veiculos as $veiculo) {
            $vcv = VeiculoConjuntoVeiculo::firstOrCreate([
                'codveiculo' => $veiculo['codveiculo'],
                'codveiculoconjunto' => $conjunto->codveiculoconjunto,
            ]);
        }
        return new VeiculoConjuntoResource($conjunto);
    }

}
