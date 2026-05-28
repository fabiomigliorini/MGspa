<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:43:21
 */

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\ColaboradorRubrica;
use Mg\Rh\PeriodoColaborador;
use Mg\Filial\Setor;

class PeriodoColaboradorSetor extends MgModel
{
    protected $table = 'tblperiodocolaboradorsetor';
    protected $primaryKey = 'codperiodocolaboradorsetor';


    protected $fillable = [
        'codperiodocolaborador',
        'codsetor',
        'diastrabalhados',
        'percentualrateio'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codperiodocolaborador' => 'integer',
        'codperiodocolaboradorsetor' => 'integer',
        'codsetor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'diastrabalhados' => 'float',
        'percentualrateio' => 'float'
    ];


    // Chaves Estrangeiras
    public function PeriodoColaborador()
    {
        return $this->belongsTo(PeriodoColaborador::class, 'codperiodocolaborador', 'codperiodocolaborador');
    }

    public function Setor()
    {
        return $this->belongsTo(Setor::class, 'codsetor', 'codsetor');
    }


    // Tabelas Filhas
    public function ColaboradorRubricaS()
    {
        return $this->hasMany(ColaboradorRubrica::class, 'codperiodocolaboradorsetor', 'codperiodocolaboradorsetor');
    }

}
