<?php

namespace Mg\Veiculo;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

class VeiculoController
{
    public function index (Request $request)
    {
        $veiculos = Veiculo::orderBy('placa')->get();
        return $veiculos;
    }

    public function show (Request $request, $id)
    {
        $veiculo = Veiculo::findOrFail($id);
        return $veiculo;
    }
    //
    // public function update (Request $request, $id)
    // {
    //
    //     $request->validate([
    //         'Veiculo' => [
    //             'required',
    //             Rule::unique('tblVeiculo')->ignore($id, 'codVeiculo'),
    //             'min:5',
    //             'max:50',
    //         ],
    //         'tracao' => ['required'],
    //         'reboque' => ['required'],
    //         'tiporodado' => ['required'],
    //         'tipocarroceria' => ['required'],
    //     ], [
    //     ]);
    //
    //     $request->validate([
    //     ]);
    //     $veiculo = Veiculo::findOrFail($id);
    //     $veiculo->fill($request->all());
    //     $veiculo->save();
    //     return $veiculo;
    // }
    //
    public function inativar (Request $request, $id)
    {
        $veiculo = Veiculo::findOrFail($id);
        $veiculo->update([
            'inativo' => Carbon::now()
        ]);
        return $veiculo;
    }

    public function ativar (Request $request, $id)
    {
        $veiculo = Veiculo::findOrFail($id);
        $ret = $veiculo->update([
            'inativo' => null
        ]);
        return $veiculo;
    }

    public function delete (Request $request, $id)
    {
        $veiculo = Veiculo::findOrFail($id);
        $veiculo->delete();
    }
    //
    // public function store (Request $request)
    // {
    //     $request->validate([
    //         'Veiculo' => ['required', 'unique:tblVeiculo', 'min:5', 'max:50'],
    //         'tracao' => ['required'],
    //         'reboque' => ['required'],
    //         'tiporodado' => ['required'],
    //         'tipocarroceria' => ['required'],
    //     ]);
    //     $veiculo = Veiculo::create($request->all());
    //     return $veiculo;
    // }

}
