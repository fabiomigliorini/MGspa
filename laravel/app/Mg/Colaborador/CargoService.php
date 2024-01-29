<?php

namespace Mg\Colaborador;

use Carbon\Carbon;

class CargoService
{

    public static function create($data)
    {
        $cargo = new Cargo($data);
        $cargo->save();
        return $cargo->refresh();
    }

    public static function update($cargo, $data)
    {
        $cargo->fill($data);
        $cargo->save();
        return $cargo;
    }


    public static function delete($cargo)
    {
        return $cargo->delete();
    }

    public static function inativar(Cargo $cargo)
    {
        $cargo->update(['inativo' => Carbon::now()]);
        return $cargo->refresh();
    }

    public static function ativar($cargo)
    {
        $cargo->inativo = null;
        $cargo->update();
        return $cargo;
    }

  
}
