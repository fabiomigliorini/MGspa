<?php

namespace Mg\Veiculo;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use Mg\NotaFiscal\NotaFiscal;

class VeiculoTipoController
{
    public function index (Request $request)
    {
        $tipos = VeiculoTipo::orderBy('veiculotipo')->get();
        return $tipos;
    }

    public function show (Request $request, $id)
    {
        $tipo = VeiculoTipo::findOrFail($id);
        return $tipo;
    }

    public function update (Request $request, $id)
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
        ], [
        ]);

        $request->validate([
        ]);
        $tipo = VeiculoTipo::findOrFail($id);
        $tipo->fill($request->all());
        $tipo->save();
        return $tipo;
    }

    public function inativar (Request $request, $id)
    {
        $tipo = VeiculoTipo::findOrFail($id);
        $tipo->update([
            'inativo' => Carbon::now()
        ]);
        return $tipo;
    }

    public function ativar (Request $request, $id)
    {
        $tipo = VeiculoTipo::findOrFail($id);
        $ret = $tipo->update([
            'inativo' => null
        ]);
        return $tipo;
    }

    public function delete (Request $request, $id)
    {
        $tipo = VeiculoTipo::findOrFail($id);
        $tipo->delete();
    }

    public function store (Request $request)
    {
        $request->validate([
            'veiculotipo' => ['required', 'unique:tblveiculotipo', 'min:5', 'max:50'],
            'tracao' => ['required'],
            'reboque' => ['required'],
            'tiporodado' => ['required'],
            'tipocarroceria' => ['required'],
        ]);
        $tipo = VeiculoTipo::create($request->all());
        return $tipo;
    }

}
