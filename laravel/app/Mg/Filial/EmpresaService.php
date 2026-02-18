<?php

namespace Mg\Filial;

class EmpresaService
{
    public static function index($empresa = null, $codempresa = null)
    {
        $query = Empresa::orderBy('codempresa', 'asc');

        if (!empty($empresa)) {
            $query->where('empresa', 'ilike', "%{$empresa}%");
        }

        if (!empty($codempresa)) {
            $query->where('codempresa', $codempresa);
        }

        return $query->get();
    }

    public static function create(array $data)
    {
        $empresa = new Empresa($data);
        $empresa->save();
        return $empresa->refresh();
    }

    public static function update(Empresa $empresa, array $data)
    {
        $empresa->fill($data);
        $empresa->save();
        return $empresa;
    }

    public static function delete(Empresa $empresa)
    {
        return $empresa->delete();
    }
}
