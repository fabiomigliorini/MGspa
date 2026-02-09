<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Exception;

class DependenteService
{
    const TIPDEP_CONJUGE                     = '01';
    const TIPDEP_COMPANHEIRO                 = '02';
    const TIPDEP_FILHO_ATE_21                = '03';
    const TIPDEP_FILHO_UNIVERSITARIO         = '04';
    const TIPDEP_IRMAO_NETO_ATE_21           = '06';
    const TIPDEP_IRMAO_NETO_UNIVERSITARIO    = '07';
    const TIPDEP_PAIS_AVOS                   = '09';
    const TIPDEP_MENOR_GUARDA_JUDICIAL       = '10';
    const TIPDEP_INCAPAZ                     = '11';
    const TIPDEP_EX_CONJUGE                  = '12';
    const TIPDEP_AGREGADO_OUTROS             = '99';

    const TIPDEP_LABELS = [
        self::TIPDEP_CONJUGE                     => 'Cônjuge',
        self::TIPDEP_COMPANHEIRO                 => 'Companheiro(a) com filho ou união >5 anos',
        self::TIPDEP_FILHO_ATE_21                => 'Filho(a) ou enteado(a) até 21 anos',
        self::TIPDEP_FILHO_UNIVERSITARIO         => 'Filho(a) ou enteado(a) universitário ou cursando escola técnica até 24 anos',
        self::TIPDEP_IRMAO_NETO_ATE_21           => 'Irmão(ã), neto(a) ou bisneto(a) sob guarda até 21 anos',
        self::TIPDEP_IRMAO_NETO_UNIVERSITARIO    => 'Irmão(ã), neto(a) ou bisneto(a) sob guarda universitário até 24 anos',
        self::TIPDEP_PAIS_AVOS                   => 'Pais, avós e bisavós',
        self::TIPDEP_MENOR_GUARDA_JUDICIAL       => 'Menor pobre até 21 anos sob guarda judicial',
        self::TIPDEP_INCAPAZ                     => 'Pessoa absolutamente incapaz da qual seja tutor ou curador',
        self::TIPDEP_EX_CONJUGE                  => 'Ex-cônjuge que recebe pensão alimentícia',
        self::TIPDEP_AGREGADO_OUTROS             => 'Agregado/Outros',
    ];

    const TIPDEP_IDADE_LIMITE = [
        self::TIPDEP_FILHO_ATE_21              => 21,
        self::TIPDEP_FILHO_UNIVERSITARIO       => 24,
        self::TIPDEP_IRMAO_NETO_ATE_21         => 21,
        self::TIPDEP_IRMAO_NETO_UNIVERSITARIO  => 24,
        self::TIPDEP_MENOR_GUARDA_JUDICIAL     => 21,
    ];

    const TIPDEP_PERMITE_IRRF = [
        self::TIPDEP_CONJUGE,
        self::TIPDEP_COMPANHEIRO,
        self::TIPDEP_FILHO_ATE_21,
        self::TIPDEP_FILHO_UNIVERSITARIO,
        self::TIPDEP_IRMAO_NETO_ATE_21,
        self::TIPDEP_IRMAO_NETO_UNIVERSITARIO,
        self::TIPDEP_PAIS_AVOS,
        self::TIPDEP_MENOR_GUARDA_JUDICIAL,
        self::TIPDEP_INCAPAZ,
        self::TIPDEP_EX_CONJUGE,
    ];

    const TIPDEP_PERMITE_SALFAM = [
        self::TIPDEP_FILHO_ATE_21,
        self::TIPDEP_IRMAO_NETO_ATE_21,
        self::TIPDEP_MENOR_GUARDA_JUDICIAL,
    ];

    public static function create($data)
    {
        $dependente = new Dependente($data);
        $dependente->save();
        return $dependente->refresh();
    }

    public static function update($dependente, $data)
    {
        $dependente->fill($data);
        $dependente->save();
        return $dependente;
    }

    public static function delete($dependente)
    {
        return $dependente->delete();
    }

    public static function ativar($model)
    {
        $model->inativo = null;
        $model->update();
        return $model;
    }

    public static function inativar($model, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $model->inativo = $date;
        $model->update();
        return $model;
    }
}
