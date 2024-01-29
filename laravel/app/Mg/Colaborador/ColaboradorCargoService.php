<?php

namespace Mg\Colaborador;

class ColaboradorCargoService
{

    public static function create($data)
    {
        $colaboradorCargo = new ColaboradorCargo($data);
        $colaboradorCargo->save();
        return $colaboradorCargo->refresh();
    }

    public static function update($colaboradorCargo, $data)
    {
        $colaboradorCargo->fill($data);
        $colaboradorCargo->save();
        return $colaboradorCargo;
    }


    public static function delete($colaboradorCargo)
    {
        return $colaboradorCargo->delete();
    }

    // public static function inativar(GrupoEconomico $grupo)
    // {
    //     $grupo->update(['inativo' => Carbon::now()]);
    //     return $grupo->refresh();
    // }

    public static function ativar($grupo)
    {
        $grupo->inativo = null;
        $grupo->update();
        return $grupo;
    }

  
}
