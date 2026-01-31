<?php

namespace Mg\Pessoa;

use Carbon\Carbon;

class EtniaService
{
    public static function index($etnia = null, $inativo = null)
    {
        $query = Etnia::orderBy('etnia');

        if (!empty($etnia)) {
            $query->where('etnia', 'ilike', "%{$etnia}%");
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
        $etnia = new Etnia($data);
        $etnia->save();
        return $etnia->refresh();
    }

    public static function update(Etnia $etnia, array $data)
    {
        $etnia->fill($data);
        $etnia->save();
        return $etnia;
    }

    public static function delete(Etnia $etnia)
    {
        return $etnia->delete();
    }

    public static function ativar(Etnia $etnia)
    {
        $etnia->inativo = null;
        $etnia->save();
        return $etnia;
    }

    public static function inativar(Etnia $etnia, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $etnia->inativo = $date;
        $etnia->save();
        return $etnia;
    }
}
