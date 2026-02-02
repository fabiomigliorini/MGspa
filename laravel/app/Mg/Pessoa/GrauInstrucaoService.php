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

    public static function create(array $data)
    {
        $grauInstrucao = new GrauInstrucao($data);
        $grauInstrucao->save();
        return $grauInstrucao->refresh();
    }

    public static function update(GrauInstrucao $grauInstrucao, array $data)
    {
        $grauInstrucao->fill($data);
        $grauInstrucao->save();
        return $grauInstrucao;
    }

    public static function delete(GrauInstrucao $grauInstrucao)
    {
        return $grauInstrucao->delete();
    }

    public static function ativar(GrauInstrucao $grauInstrucao)
    {
        $grauInstrucao->inativo = null;
        $grauInstrucao->save();
        return $grauInstrucao;
    }

    public static function inativar(GrauInstrucao $grauInstrucao, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $grauInstrucao->inativo = $date;
        $grauInstrucao->save();
        return $grauInstrucao;
    }
}
