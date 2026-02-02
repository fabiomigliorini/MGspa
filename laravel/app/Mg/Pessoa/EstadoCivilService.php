<?php

namespace Mg\Pessoa;

use Carbon\Carbon;

class EstadoCivilService
{
    public static function index($estadocivil = null, $status = null)
    {
        $query = EstadoCivil::orderBy('estadocivil');

        if (!empty($estadocivil)) {
            $query->where('estadocivil', 'ilike', "%{$estadocivil}%");
        }

        switch ($status) {
            case 'inativos':
                $query->whereNotNull('inativo');
                break;

            case 'ativos':
                $query->whereNull('inativo');
                break;

            case 'todos':
            default:
                break;
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
