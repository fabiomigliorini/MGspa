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

    public static function create(array $data): EstadoCivil
    {
        $reg = new EstadoCivil($data);
        $reg->save();
        return $reg->refresh();
    }

    public static function update(EstadoCivil $reg, array $data): EstadoCivil
    {
        $reg->fill($data);
        $reg->save();
        return $reg;
    }

    public static function delete(EstadoCivil $reg)
    {
        return $reg->delete();
    }

    public static function ativar(EstadoCivil $reg): EstadoCivil
    {
        $reg->inativo = null;
        $reg->save();
        return $reg;
    }

    public static function inativar(EstadoCivil $reg, $date = null): EstadoCivil
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $reg->inativo = $date;
        $reg->save();
        return $reg;
    }
}
