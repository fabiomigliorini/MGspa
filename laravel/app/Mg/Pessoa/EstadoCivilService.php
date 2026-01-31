<?php

namespace Mg\Pessoa;

use Carbon\Carbon;

class EstadoCivilService
{
    public static function index($estadocivil = null, $inativo = null)
    {
        $query = EstadoCivil::orderBy('estadocivil');

        if (!empty($estadocivil)) {
            $query->where('estadocivil', 'ilike', "%{$estadocivil}%");
        }

        if ($inativo === false) {
            $query->whereNull('inativo');
        } elseif ($inativo === true) {
            $query->whereNotNull('inativo');
        }

        return $query->get();
    }

    public static function create(array $data)
    {
        $estadoCivil = new EstadoCivil($data);
        $estadoCivil->save();
        return $estadoCivil->refresh();
    }

    public static function update(EstadoCivil $estadoCivil, array $data)
    {
        $estadoCivil->fill($data);
        $estadoCivil->save();
        return $estadoCivil;
    }

    public static function delete(EstadoCivil $estadoCivil)
    {
        return $estadoCivil->delete();
    }

    public static function ativar(EstadoCivil $estadoCivil)
    {
        $estadoCivil->inativo = null;
        $estadoCivil->save();
        return $estadoCivil;
    }

    public static function inativar(EstadoCivil $estadoCivil, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $estadoCivil->inativo = $date;
        $estadoCivil->save();
        return $estadoCivil;
    }
}
