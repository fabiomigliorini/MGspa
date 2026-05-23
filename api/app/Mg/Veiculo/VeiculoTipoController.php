<?php

namespace Mg\Veiculo;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VeiculoTipoController
{
    public function index(Request $request)
    {
        return VeiculoTipo::orderBy('veiculotipo')->get();
    }

    public function show(Request $request, $id)
    {
        return VeiculoTipo::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'veiculotipo' => [
                'required',
                Rule::unique('tblveiculotipo')->ignore($id, 'codveiculotipo'),
                'min:5',
                'max:50',
            ],
            'tracao' => ['required'],
            'reboque' => ['required'],
            'tiporodado' => ['required'],
            'tipocarroceria' => ['required'],
        ]);

        $tipo = VeiculoTipo::findOrFail($id);
        $tipo->fill($request->all());
        $tipo->save();
        return $tipo;
    }

    public function inativar(Request $request, $id)
    {
        $tipo = VeiculoTipo::findOrFail($id);
        $tipo->update(['inativo' => Carbon::now()]);
        return $tipo;
    }

    public function ativar(Request $request, $id)
    {
        $tipo = VeiculoTipo::findOrFail($id);
        $tipo->update(['inativo' => null]);
        return $tipo;
    }

    public function delete(Request $request, $id)
    {
        $tipo = VeiculoTipo::findOrFail($id);
        $tipo->delete();
    }

    public function store(Request $request)
    {
        $request->validate([
            'veiculotipo' => ['required', 'unique:tblveiculotipo', 'min:5', 'max:50'],
            'tracao' => ['required'],
            'reboque' => ['required'],
            'tiporodado' => ['required'],
            'tipocarroceria' => ['required'],
        ]);
        return VeiculoTipo::create($request->all());
    }
}
