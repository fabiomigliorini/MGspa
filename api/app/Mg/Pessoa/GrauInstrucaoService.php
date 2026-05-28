<?php

namespace Mg\Pessoa;

use Carbon\Carbon;

class GrauInstrucaoService
{
    public static function index($grauinstrucao = null, $status = null)
    {
        $query = GrauInstrucao::orderBy('grauinstrucao');

        if (!empty($grauinstrucao)) {
            $query->where('grauinstrucao', 'ilike', "%{$grauinstrucao}%");
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

    public static function create(array $data): GrauInstrucao
    {
        $reg = new GrauInstrucao($data);
        $reg->save();
        return $reg->refresh();
    }

    public static function update(GrauInstrucao $reg, array $data): GrauInstrucao
    {
        $reg->fill($data);
        $reg->save();
        return $reg;
    }

    public static function delete(GrauInstrucao $reg)
    {
        return $reg->delete();
    }

    public static function ativar(GrauInstrucao $reg): GrauInstrucao
    {
        $reg->inativo = null;
        $reg->save();
        return $reg;
    }

    public static function inativar(GrauInstrucao $reg, $date = null): GrauInstrucao
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $reg->inativo = $date;
        $reg->save();
        return $reg;
    }
}
