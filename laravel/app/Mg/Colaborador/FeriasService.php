<?php

namespace Mg\Colaborador;

class FeriasService
{

    public static function create($data)
    {
        $ferias = new Ferias($data);
        $ferias->save();
        return $ferias->refresh();
    }

    public static function update($ferias, $data)
    {
        $ferias->fill($data);
        $ferias->save();
        return $ferias;
    }


    public static function delete($ferias)
    {
        return $ferias->delete();
    }
}
