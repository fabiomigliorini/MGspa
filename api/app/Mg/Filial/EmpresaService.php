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

    public static function create(array $data): Empresa
    {
        $reg = new Empresa($data);
        $reg->save();
        return $reg->refresh();
    }

    public static function update(Empresa $reg, array $data): Empresa
    {
        $reg->fill($data);
        $reg->save();
        return $reg;
    }

    public static function delete(Empresa $reg)
    {
        return $reg->delete();
    }
}
